import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Ensures the update button text is displayed correctly.
 */
test('Verify Update Button Text', async ({ page }, testInfo) => {
	const customMessage = 'Zap It with Newness';

	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await test.step('Create a new View with the Event Registration form', async () => {
		await createView(page, {
			formTitle: 'Event Registration',
			viewName: 'Verify Update Button Text Test',
			template: templates[0]
		}, testInfo);
	});

	await test.step('Customize Update Button Text and publish the View', async () => {
		await page
			.locator('#gravityview_settings div')
			.getByRole('link', { name: 'Edit Entry' })
			.click();
		await page.getByLabel('Update Button Text').fill(customMessage);
		await publishView(page);
	});

	await test.step('Check the View on the front end', async () => {
		await checkViewOnFrontEnd(page);
	});

	await test.step('Verify the custom Update Button Text is displayed correctly', async () => {
		await page.getByRole('link', { name: 'John Doe' }).click();
		await page.getByRole('link', { name: 'Edit Entry' }).click();
		await expect(page.getByRole('button', { name: customMessage })).toBeVisible();
	});
});
