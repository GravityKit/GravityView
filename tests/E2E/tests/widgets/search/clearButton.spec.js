import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../../helpers/test-helpers';

/**
 * Test that the "Clear" button in the search bar resets the search results to show all entries.
 */
test('Clear Search', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(page, {
		formTitle: 'Favorite Book',
		viewName: 'Clear Search Test',
		template: templates[0]
	});
	await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
	await page.getByRole('link', { name: 'Add Search Field' }).first().click();
	await page.getByLabel('Configure Submit Button').click();
	await page.getByLabel('Show Clear Button').setChecked(true);
	await page.locator('button[data-close-settings]').click();
	await page.locator('.ui-dialog').getByRole('button', { name: 'Close', exact: true }).click();
	await publishView(page);
	await checkViewOnFrontEnd(page);
	await page.getByLabel('Search Entries:').fill('Bob');
	await page.getByRole('button', { name: 'Search' }).click();
	await page.waitForURL(/.*\?gv_search.*/);
	await page.getByRole('link', { name: 'Clear', exact: true }).click();
	await expect(page.getByText('David')).toBeVisible();
});
