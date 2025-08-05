const { test, expect } = require('@playwright/test');
const {
	createView,
	publishView,
	checkViewOnFrontEnd,
	viewTemplatesMap,
} = require('../../../helpers/test-helpers');

/*
 * Verifies that the search column configuration works as expected.
 */
test('Manage Column settings in Search Widget', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	let viewUrl;

	await test.step('Set horizontal column', async () => {
		await createView(page, {
			formTitle: 'A Simple Form',
			viewName: 'Manage Rows in Search Widget',
			template: viewTemplatesMap.table
		});
		await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
		await page
			.locator('#search-search-general-fields a.gv-search-area-settings[data-areaid]')
			.click();

		await page.locator('.has-options-panel > .gv-dialog-options select[name*="[area_settings][layout]"]')
			.selectOption( 'row' );

		await page.locator('button[data-close-settings]').click();
		await page.getByRole('button', { name: 'Close', exact: true }).click();

		await publishView(page);
		viewUrl = page.url();

		await checkViewOnFrontEnd(page);

		const search = page.locator('.gv-widget-search-general-search .gv-grid-col-1-1 .gv-search-widget-area').first();
		await expect(search).toHaveClass(/gv-search-horizontal/);
	});

	await test.step('Set vertical column', async () => {

		await page.goto(viewUrl);

		await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
		await page
			.locator('#search-search-general-fields a.gv-search-area-settings[data-areaid]')
			.click();

		await page.locator('.has-options-panel > .gv-dialog-options select[name*="[area_settings][layout]"]')
			.selectOption( 'column' );

		await page.locator('button[data-close-settings]').click();
		await page.getByRole('button', { name: 'Close', exact: true }).click();

		await publishView(page);
		viewUrl = page.url();

		await checkViewOnFrontEnd(page);

		const search = page.locator('.gv-widget-search-general-search .gv-grid-col-1-1 .gv-search-widget-area').first();
		await expect(search).not.toHaveClass(/gv-search-horizontal/);
	});

});
