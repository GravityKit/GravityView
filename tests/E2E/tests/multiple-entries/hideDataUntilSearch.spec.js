import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Ensures no entries are displayed when data is hidden until search.
 */
test('Hide Data Until Search', async ({ page }, testInfo) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(
		page,
		{
			formTitle: 'Favorite Book',
			viewName: 'Hide Data Until Search Test',
			template: templates[0]
		},
		testInfo
	);
	await page
		.locator('#gravityview_settings div')
		.getByRole('link', { name: 'Multiple Entries' })
		.click();
	await page.getByLabel('Hide View data until search is performed').setChecked(true);
	await publishView(page);
	await checkViewOnFrontEnd(page);
	await expect(page.locator('body')).not.toHaveClass(/gv-table-view/);
});
