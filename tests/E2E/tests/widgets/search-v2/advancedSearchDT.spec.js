const { test, expect } = require('@playwright/test');
const {
	createView,
	publishView,
	checkViewOnFrontEnd,
	viewTemplatesMap,
	clickFirstVisible
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
		template: viewTemplatesMap.dataTables
	});
	await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
	const addSearchFieldButton = page
			.locator('#search-search-general-fields')
			.getByRole('link', { name: ' Add Search Field' });
	await expect (addSearchFieldButton).toBeVisible();
	await addSearchFieldButton.click();
	await clickFirstVisible(
		page,
		page.getByTitle('Search Field: Is Starred\nFilter on starred entries')
	);
	await page.getByRole('button', { name: ' Close' }).click();
	await publishView(page);
	await checkViewOnFrontEnd(page);

	await page.getByLabel('Search Entries:').fill('example');
	await page.getByRole('button', { name: 'Search', exact: true }).click();
	await page.getByLabel('Search Entries:').fill('');
	await page.getByLabel('Toggle Advanced Search').click();
	await page.getByLabel('Is Starred').check();
	await page.getByRole('button', { name: 'Search', exact: true }).click();
	const rows = page.locator('tbody > tr');
	await expect(rows).toHaveCount(1);
	await expect(page.getByText('David', { exact: true })).toBeVisible();
});
