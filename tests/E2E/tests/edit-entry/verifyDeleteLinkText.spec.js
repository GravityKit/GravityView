import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Validates the text of the delete link.
 */
test('Verify Delete Link Text', async ({ page }) => {
	const customMessage = 'Erase This Drama!';

	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await test.step('Create a new View with the Event Registration form', async () => {
		await createView(page, {
			formTitle: 'Event Registration',
			viewName: 'Verify Delete Link Text Test',
			template: templates[0]
		});
	});

	await test.step('Customize Delete Link Text and publish the View', async () => {
		await page
			.locator('#gravityview_settings div')
			.getByRole('link', { name: 'Edit Entry' })
			.click();
		await page.getByLabel('Delete Link Text').fill(customMessage);
		await publishView(page);
	});

	await test.step('Check the View on the front end', async () => {
		await checkViewOnFrontEnd(page);
	});

	await test.step('Verify the custom Delete Link Text is displayed correctly', async () => {
		await page.getByRole('link', { name: 'John Doe' }).click();
		await page.getByRole('link', { name: 'Edit Entry' }).click();
		await expect(page.getByRole('link', { name: customMessage })).toBeVisible();
	});
});
