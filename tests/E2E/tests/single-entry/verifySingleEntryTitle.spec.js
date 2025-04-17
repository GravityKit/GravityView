import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Ensures the title of a single entry is displayed correctly.
 */
test('Verify Single Entry Title', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(page, {
		formTitle: 'Event Registration',
		viewName: 'Verify Single Entry Title Test',
		template: templates[0]
	});
	await page
		.locator('#gravityview_settings div')
		.getByRole('link', { name: 'Single Entry' })
		.click();
	const customMessage = 'Best Single Entry Title Ever!';
	await page.getByLabel('Single Entry Title').fill(customMessage);
	await publishView(page);
	await checkViewOnFrontEnd(page);
	await page.getByRole('link', { name: 'John Doe' }).click();
	await expect(page.getByRole('heading', { name: customMessage })).toBeVisible();
});
