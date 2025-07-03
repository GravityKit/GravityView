const { test, expect } = require('@playwright/test');
const {
	createView,
	publishView,
	checkViewOnFrontEnd,
	viewTemplatesMap
} = require('../../../helpers/test-helpers');

/**
 * Ensures that search fields that are not configured or visible are not allowed to be searched on.
 */
test('Unconfigured or invisible search fields are not searchable', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(page, {
		formTitle: 'A Simple Form',
		viewName: 'Missing Configuration Invisible On Front End',
		template: viewTemplatesMap.dataTables
	});

	await page
		.locator('#directory-header-widgets')
		.getByRole('link', { name: ' Add Widget' })
		.first()
		.click();
	await page.getByRole('heading', { name: ' Search Bar Search form for' }).locator('i').click();
	await publishView(page);
	await checkViewOnFrontEnd(page);

	await expect(page.getByText('Search Mode')).not.toBeVisible();
	await expect(page.getByText('Match Any Fields')).not.toBeVisible();
	await expect(page.getByText('Match All Fields')).not.toBeVisible();
	const searchEntries = page.locator('form').filter({ hasText: 'Search Entries' });
	await expect(searchEntries).toBeVisible();
	await expect(searchEntries).toHaveCount(1);
});
