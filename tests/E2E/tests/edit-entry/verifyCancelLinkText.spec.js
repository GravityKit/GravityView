import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Checks the correct display of the cancel link text.
 */
test('Verify Cancel Link Text', async ({ page }) => {
	const customMessage = 'Forget It, Jack';

	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await test.step('Create a new View with the Event Registration form', async () => {
		await createView(page, {
			formTitle: 'Event Registration',
			viewName: 'Verify Cancel Link Text Test',
			template: templates[0]
		});
	});

	await test.step('Customize Cancel Link Text and publish the View', async () => {
		await page
			.locator('#gravityview_settings div')
			.getByRole('link', { name: 'Edit Entry' })
			.click();
		await page.getByLabel('Cancel Link Text').fill(customMessage);
		await publishView(page);
	});

	await test.step('Check the View on the front end', async () => {
		await checkViewOnFrontEnd(page);
	});

	await test.step('Verify the custom Cancel Link Text is displayed correctly', async () => {
		await page.getByRole('link', { name: 'John Doe' }).click();
		await page.getByRole('link', { name: 'Edit Entry' }).click();
		await expect(page.getByRole('link', { name: customMessage })).toBeVisible();
	});
});
