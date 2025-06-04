const { test, expect } = require('@playwright/test');
const {
	createView,
	publishView,
	checkViewOnFrontEnd,
	viewTemplatesMap
} = require('../../../helpers/test-helpers');

/*
Verifies that the 'Search Mode Visible - Match Any Fields' functionality displays all matching entries when searching in the table template.
*/
test('Search Mode Visible - Match Any Fields', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await createView(page, {
		formTitle: 'Training Feedback',
		viewName: 'Search Mode Visible - Match Any Fields',
		template: viewTemplatesMap.table
	});

	await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
	await page.getByLabel('Configure Search mode Settings').click();
	await page.getByLabel('Input type Hidden Field Radio').selectOption('radio');
	await page.getByRole('button', { name: 'Close settings pane' }).click();
	await page.getByRole('button', { name: 'Close', exact: true }).click();

	await publishView(page);
	await checkViewOnFrontEnd(page);

	await page.getByLabel('Search Entries:').fill('Clara training');
	await page.getByRole('button', { name: 'Search' }).click();
	const tableBody = page.locator('.gv-table-view >> tbody');

	const rows = tableBody.locator('tr');
	await expect(rows).toHaveCount(3);
	await expect(page.getByRole('link', { name: 'Clara Thompson' })).toBeVisible();
	await expect(page.getByRole('link', { name: 'Jason Lee' })).toBeVisible();
	await expect(page.getByRole('link', { name: 'Priya Desai' })).toBeVisible();
});
