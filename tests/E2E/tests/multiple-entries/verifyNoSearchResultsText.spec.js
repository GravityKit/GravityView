import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Confirms the text displayed when a search yields no results.
 */
test('Verify No Search Results Custom Message', async ({ page }, testInfo) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(
		page,
		{
			formTitle: 'No Entries',
			viewName: 'Verify No Search Results Message Test',
			template: templates[0]
		},
		testInfo
	);

	await page
		.locator('#gravityview_settings div')
		.getByRole('link', { name: 'Multiple Entries' })
		.click();
	const customMessage = 'Empty! Did the entries vanish into thin air?';
	await page.getByPlaceholder('This search returned no').fill(customMessage);

	await publishView(page);
	await checkViewOnFrontEnd(page);

	await page.getByLabel('Search Entries:').fill('Something random, ya know?');
	await page.getByRole('button', { name: 'Search' }).click();
	await page.waitForURL(/.*\?gv_search.*/);
	await expect(page.getByText(customMessage)).toBeVisible();
});
