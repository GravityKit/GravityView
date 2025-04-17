import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Checks the backlink label displays correctly.
 */
test('Verify Back Link Label', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(page, {
		formTitle: 'Event Registration',
		viewName: 'Verify Back Link Label Test',
		template: templates[0]
	});
	await page
		.locator('#gravityview_settings div')
		.getByRole('link', { name: 'Single Entry' })
		.click();
	const customMessage = 'Return to the Scene of the Crime';
	await page.getByPlaceholder('Go back').fill(customMessage);
	await publishView(page);
	await checkViewOnFrontEnd(page);
	await page.getByRole('link', { name: 'John Doe' }).click();
	await expect(page.getByRole('link', { name: customMessage })).toBeVisible();
});
