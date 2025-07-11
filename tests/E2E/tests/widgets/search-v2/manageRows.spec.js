const { test, expect } = require('@playwright/test');
const {
	createView,
	publishView,
	checkViewOnFrontEnd,
	viewTemplatesMap,
	clickFirstVisible,
	expectToBeVisibleBefore
} = require('../../../helpers/test-helpers');

/*
 * Verifies that the advanced search panel works as expected. Particularly, after performing
 * a search with regular search fields first.
 */

test('Manage Rows in Search Widget', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	let viewUrl;

	await test.step('Add Row', async () => {
		await createView(page, {
			formTitle: 'A Simple Form',
			viewName: 'Manage Rows in Search Widget',
			template: viewTemplatesMap.table
		});
		await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
		await page
			.locator('#search-search-general-fields')
			.getByRole('button', { name: ' Add Row' })
			.click();

		await page.locator('button[data-add-row="search-general"][data-row-type="33/66"]').click();
		await clickFirstVisible(page, 'a[data-areaid*="search-general_right::33/66"].gv-add-field');
		await page.locator('[data-fieldid="is_read"].gv-fields').last().click();
		await page.locator('button.ui-dialog-titlebar-close').first().click();

		await publishView(page);
		viewUrl = page.url();

		await checkViewOnFrontEnd(page);

		const search = page.locator('input[name="gv_search"]').first();
		const is_read = page.locator('select[name="filter_is_read"]').first();

		await expectToBeVisibleBefore(page, search, is_read);
	});

	await test.step('Move Row', async () => {
		await page.goto(viewUrl);
		await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();

		const top_row = page.locator('#search-search-general-fields .gv-grid-row').first();
		const bottom_row = page.locator('#search-search-general-fields .gv-grid-row').last();

		await bottom_row.hover();
		await bottom_row.locator('.gv-grid-row-handle').dragTo(top_row);

		await page.locator('button.ui-dialog-titlebar-close').first().click();
		await publishView(page);
		await checkViewOnFrontEnd(page);

		const search = page.locator('input[name="gv_search"]').first();
		const is_read = page.locator('select[name="filter_is_read"]').first();

		await expectToBeVisibleBefore(page, is_read, search);
	});

	await test.step('Remove Row', async () => {
		await page.goto(viewUrl);
		await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();

		const row = page.locator('#search-search-general-fields .gv-grid-row').first();
		await row.hover();
		page.on('dialog', (dialog) => dialog.accept());
		await row.locator('.gv-grid-row-delete').click();

		await page.locator('button.ui-dialog-titlebar-close').first().click();
		await publishView(page);
		await checkViewOnFrontEnd(page);

		const search = page.locator('input[name="gv_search"]').first();
		const is_read = page.locator('select[name="filter_is_read"]');

		await expect(search).toBeVisible();
		await expect(is_read).not.toBeAttached();
	});
});
