const { test, expect } = require('@playwright/test');
const {
	createView,
	publishView,
	checkViewOnFrontEnd,
	viewTemplatesMap
} = require('../../../helpers/test-helpers');

/*
 * This test verifies that the Search Bar is hidden for logged-out users when the visibility option is enabled.
 */
test('search bar is hidden when user is logged out', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(page, {
		formTitle: 'A Simple Form',
		viewName: 'Hidden From Logged Out Users',
		template: viewTemplatesMap.table
	});
	await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
	await page.getByLabel('Configure Search Everything').click();
	await page.getByRole('checkbox', { name: /Make visible only to logged/i }).check();
	await page.getByRole('button', { name: 'Close settings pane' }).click();
	await page.getByRole('button', { name: 'Close', exact: true }).click();

	await publishView(page);
	await checkViewOnFrontEnd(page);
	const viewUrl = page.url();
	const context = await page.context().browser().newContext({ storageState: undefined });
	const loggedOutPage = await context.newPage();
	await loggedOutPage.goto(viewUrl);

	await expect(loggedOutPage.getByLabel('Search Entries:')).not.toBeVisible();
	await context.close();
});
