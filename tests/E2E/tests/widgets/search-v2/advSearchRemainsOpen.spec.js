const { test, expect } = require('@playwright/test');
const {
	createView,
	publishView,
	checkViewOnFrontEnd,
	viewTemplatesMap,
	clickFirstVisible
} = require('../../../helpers/test-helpers');

/*
 * Verifies that the advanced search panel remains open after performing a search that employs at least
 * one advanced search field.
 */

test('Advanced Search Panel Remains Open After Search', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(page, {
		formTitle: 'Training Feedback',
		viewName: 'Verify Adv Search Remains Open',
		template: viewTemplatesMap.table
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

	await page.getByLabel('Toggle Advanced Search').click();
	await page.getByLabel('Is Starred').check();
	await page.getByRole('button', { name: 'Search', exact: true }).click();

	await expect(page.getByLabel('Is Starred')).toBeVisible();
});
