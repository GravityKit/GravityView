const { test, expect } = require('@playwright/test');
const {
	createView,
	publishView,
	checkViewOnFrontEnd,
	clickFirstVisible,
	viewTemplatesMap
} = require('../../../helpers/test-helpers');

/**
 * Verifies that the search functionality works correctly on Views with multiple search bar widgets.
 */
test('Multiple Search Bar Widgets', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await createView(page, {
		formTitle: 'A Simple Form',
		viewName: 'Search Test with Multiple Search Bar Widgets',
		template: viewTemplatesMap.table
	});

	await page
		.locator('#directory-header-widgets')
		.getByRole('link', { name: /Add Widget/ })
		.first()
		.click();
	await page.locator('#ui-id-15').getByText('Add "Search Bar" Search').click();

	await page
		.getByRole('heading', { name: /Configure Search Bar Settings/ })
		.getByLabel('Configure Search Bar Settings')
		.nth(1)
		.click();
	const addSearchFieldButton = page
		.getByLabel('Search Bar Settings', { exact: true })
		.locator('#search-search-general-fields')
		.getByRole('link', { name: /Add Search Field/ });
	await expect (addSearchFieldButton).toBeVisible();
	await addSearchFieldButton.click();
	await page
		.locator('#ui-id-19')
		.getByText('Add "Search Everything" Search EverythingSearch across all entry fields')
		.click();
	await clickFirstVisible(page, page.getByRole('button', { name: /Close/ }));

	await publishView(page);
	await checkViewOnFrontEnd(page);

	const form1 = page.locator('form.gv-widget-search').nth(0);
	const form2 = page.locator('form.gv-widget-search').nth(1);

	const searchInput1 = form1.locator('input[name="gv_search"]');
	const searchInput2 = form2.locator('input[name="gv_search"]');

	await searchInput1.click();
	await searchInput1.fill('Bob');
	await form1.getByRole('button', { name: /Search/ }).click();
	await expect(page.getByRole('link', { name: 'Bob' })).toBeVisible();

	await expect(searchInput2).toBeVisible();
	await searchInput2.fill('Charlie');
	await form2.getByRole('button', { name: /Search/ }).click();
	await expect(page.getByRole('link', { name: 'Charlie' })).toBeVisible();
});
