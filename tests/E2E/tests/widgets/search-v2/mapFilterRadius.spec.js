const { test, expect } = require('@playwright/test');
const { execSync } = require('child_process');
const { loadEnv } = require('../../../helpers/misc');
const {
	createView,
	publishView,
	checkViewOnFrontEnd,
	viewTemplatesMap
} = require('../../../helpers/test-helpers');

// Load .env variables
loadEnv();

function runWpCli(command) {
	try {
		execSync(`npm run wp-env:cli wp ${command}`, {
			stdio: 'inherit',
			timeout: 60000
		});
	} catch (error) {
		console.error(`Error running wp-cli command: wp ${command}`);
		throw error;
	}
}

test.describe('GravityMaps: Map Filter Radius Search – setup, configuration, and front-end filtering', () => {
	test.beforeAll(() => {
		runWpCli('gk products install gk-gravitymaps');
		runWpCli('gk products activate gk-gravitymaps');
	});

	test.afterAll(() => {
		runWpCli('plugin deactivate gravityview-maps');
	});

	test.beforeEach(async ({ page }) => {
		const apiKey = process.env.GOOGLE_MAPS_API_KEY;
		if (!apiKey) {
			test.skip('No Google Maps API key found in .env (GOOGLE_MAPS_API_KEY). Skipping test.');
		}
		await page.goto('/wp-admin/admin.php?page=gk_settings&p=gravitymaps&s=0');
		await page.getByRole('textbox', { name: 'Google Maps API Key' }).fill(apiKey);
		await page.getByRole('article').getByRole('button', { name: 'Save Settings' }).click();

		// Enable GV API
		await page.goto('/wp-admin/admin.php?page=gk_settings&p=gravityview&s=0');
		const toggle = page.locator('#rest_api-input');

		const isEnabled = await toggle.evaluate((el) => el.classList.contains('bg-blue-gv'));

		if (!isEnabled) {
			await toggle.click();
			console.log('API enabled');
		} else {
			console.log('API already enabled');
		}
		await page.getByRole('article').getByRole('button', { name: 'Save Settings' }).click();
	});

	test('should filter entries by geolocation radius on the map', async ({ page }) => {

		await test.step('Create View with Map template', async () => {
			await page.goto('/wp-admin/edit.php?post_type=gravityview');
			await createView(page, {
				formTitle: 'Landmark Directory',
				viewName: 'Map Filter Radius Search',
				template: viewTemplatesMap.map
			});
		});

		await test.step('Add Maps widget and configure search bar', async () => {
			// Add Maps widget
			await page.getByRole('link', { name: ' Add Field' }).nth(1).click();
			await page.getByRole('heading', { name: ' Landmark Name' }).locator('i').click();

			await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
			await page
				.locator('#search-search-general-fields')
				.getByRole('link', { name: ' Add Search Field' })
				.click();
			await page.getByRole('heading', { name: ' Geolocation Radius' }).locator('i').click();
			await page
				.getByLabel('Search Bar Settings', { exact: true })
				.getByRole('button', { name: 'Close', exact: true })
				.click();
		});

		await test.step('Enable Geocoding', async () => {
			await page.getByRole('link', { name: '   Maps' }).click();
			await page.getByLabel('Geocoding').check();
		});

		await test.step('Publish the View', async () => {
			await publishView(page);
		});

		await test.step('Check View on front end', async () => {
			await checkViewOnFrontEnd(page);
			await expect(page.getByText('Please enable the Rest API in')).not.toBeVisible();
			await expect(page.getByRole('heading', { name: 'Lincoln Memorial' })).toBeVisible();
			await page.getByPlaceholder('Enter a location').click();
			await page.getByPlaceholder('Enter a location').fill('United States Capitol');
			await page.getByText('United States CapitolWashington, DC, USA').click();
			await page.locator('#search-box-filter_geolocation').selectOption('1');
			await page.getByRole('button', { name: 'Search', exact: true }).click();
			await expect(page.getByRole('heading', { name: 'United States Capitol' })).toBeVisible();
			await expect(page.getByRole('heading', { name: 'National Gallery of Art' })).toBeVisible();
            await expect(page.getByRole('heading', { name: 'Smithsonian National Air and' })).toBeVisible();
            // TODO: Add additional assertions after bug is fixed
            // await expect(page.getByRole('heading', { name: 'Lincoln Memorial' })).not.toBeVisible();
            // await expect(page.getByRole('heading', { name: 'Washington Monument' })).not.toBeVisible();
            // await expect(page.getByRole('heading', { name: 'The White House' })).not.toBeVisible();
		});
	});
});
