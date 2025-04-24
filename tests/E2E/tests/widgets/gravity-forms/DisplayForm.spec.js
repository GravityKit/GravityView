import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	getOptionValueBySearchTerm,
	publishView,
	templates
} from '../../../helpers/test-helpers';

test('Display Form', async ({ page }) => {
	await test.step('Login and navigate to the View creation page', async () => {
		await page.goto('/wp-admin/edit.php?post_type=gravityview');
	});

	await test.step('Create and publish the View', async () => {
		await createView(page, {
			formTitle: 'A Simple Form',
			viewName: 'Display Form Test',
			template: templates[0]
		});
		await publishView(page);
	});

	await test.step('Add widget and configure Gravity Forms', async () => {
		await page
			.locator('#directory-header-widgets')
			.getByRole('link', { name: 'Add Widget' })
			.first()
			.click();

		await page.locator('#ui-id-15').getByText('Add "Gravity Forms" Gravity').click();

		await page.getByRole('button', { name: 'Configure Gravity Forms' }).click();

		const dropdownSelector = '.gv-setting-container-widget_form_id select';
		try {
			const optionValue = await getOptionValueBySearchTerm(
				page,
				dropdownSelector,
				'A Simple Form'
			);
			await page.selectOption(dropdownSelector, optionValue);
		} catch (error) {
			console.error('Failed to configure Gravity Forms:', error);
			throw error;
		}

		await page
			.getByLabel('Gravity Forms Settings', { exact: true })
			.getByRole('button', { name: 'Close', exact: true })
			.click();

		await page.getByRole('button', { name: 'Update' }).click();
		await page.getByText('View updated. View on website.').waitFor();
	});

	await test.step('Check the View on the front end', async () => {
		await checkViewOnFrontEnd(page);
		const firstNameField = page.getByLabel('First Name(Required)');
		await expect(firstNameField).toBeVisible();
	});
});
