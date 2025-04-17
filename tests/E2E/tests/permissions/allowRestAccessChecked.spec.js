import { test, expect } from '@playwright/test';
import { createView, publishView, templates } from '../../helpers/test-helpers';

/**
 * Verifies that REST API access is enabled when 'Allow REST Access' is checked.
 */
test('Verify Allow Rest Access Checked', async ({ page }) => {
	let currentUrl, params, viewId, apiUrl, response;

	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await test.step('Create a new View with the Favorite Color form', async () => {
		await createView(page, {
			formTitle: 'Favorite Color',
			viewName: 'Verify Allow Rest Access Checked Test',
			template: templates[0]
		});
	});

	await test.step('Enable the Allow REST Access setting', async () => {
		await page
			.locator('#gravityview_settings div')
			.getByRole('link', { name: 'Permissions' })
			.click();
		await page.getByLabel('Allow REST Access').setChecked(true);
		await publishView(page);
	});

	await test.step('Generate the API URL for the View', async () => {
		currentUrl = page.url();
		params = new URLSearchParams(new URL(currentUrl).search);
		viewId = params.get('post');
		apiUrl = `/wp-json/gravityview/v1/views/${viewId}`;
	});

	let responsePromise;
	await test.step('Wait for the REST API response and visit the API URL', async () => {
		responsePromise = page.waitForResponse(apiUrl);
		await page.goto(apiUrl);
	});

	await test.step('Verify that the REST API request is successful', async () => {
		response = await responsePromise;
		expect(response.ok()).toBe(true);
	});
});
