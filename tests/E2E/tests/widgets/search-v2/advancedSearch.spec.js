const { test, expect } = require('@playwright/test');
const {
	createView,
	publishView,
	checkViewOnFrontEnd,
	viewTemplatesMap,
	clickFirstVisible,
	clickAddSearchField
} = require('../../../helpers/test-helpers');

/*
 * Verifies that the advanced search panel works as expected. Particularly, after performing
 * a search with regular search fields first.
 */

test('Advanced Search Panel Works After Regular Search', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(page, {
		formTitle: 'A Simple Form',
		viewName: 'Verify Adv Search Remains Open',
		template: viewTemplatesMap.table
	});
	await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
	await clickAddSearchField(page);
	const isStarredField = page.locator('#ui-id-17').getByTitle('Search Field: Is Starred\nFilter on starred entries');
	await expect(isStarredField).toBeVisible();
	await isStarredField.click();
	await page.waitForTimeout(1000);
	await page.getByRole('button', { name: ' Close' }).click();
	await publishView(page);
	await checkViewOnFrontEnd(page);

	await page.getByLabel('Search Entries:').fill('example');
	await page.getByRole('button', { name: 'Search', exact: true }).click();
	await page.getByLabel('Search Entries:').fill('');
	await page.getByLabel('Is Starred').check();
	await page.getByRole('button', { name: 'Search', exact: true }).click();
	const rows = page.locator('tbody > tr');
	await expect(rows).toHaveCount(1);
	await expect(page.getByText('David', { exact: true })).toBeVisible();
});
