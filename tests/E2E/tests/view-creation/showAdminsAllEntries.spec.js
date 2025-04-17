import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	publishView,
	selectGravityFormByTitle
} from '../../helpers/test-helpers';

/**
 * Verifies that admins can see all entries when the checkbox is enabled.
 */
test('Verify Admins Can See All Entries Regardless of Approval Status', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await page.getByText('Add New View', { exact: true }).click();

	await page.getByLabel('Enter View name here').click();
	await page.getByLabel('Enter View name here').fill('Admin All Entries Visibility Test');

	const form = {
		filename: 'simple',
		title: 'A Simple Form'
	};
	await selectGravityFormByTitle(page, form.title);

	await page.waitForSelector('#gravityview_select_template', {
		state: 'visible'
	});
	await page.waitForSelector('.gv-view-types-module', { state: 'visible' });

	const tableTemplateSelector = await page.$(
		'div.gv-view-types-module:has(a.gv_select_template[href="#gv_select_template"][data-templateid="default_table"])'
	);
	if (!tableTemplateSelector) {
		throw new Error('Table template not found.');
	}
	await tableTemplateSelector.hover();
	const selectButtonLocator = page.locator(
		'a.gv_select_template[data-templateid="default_table"]'
	);
	await selectButtonLocator.waitFor({ state: 'visible' });
	await selectButtonLocator.click();

	await page.getByLabel('Show only approved entries').setChecked(false);

	await publishView(page);
	await checkViewOnFrontEnd(page);
	await expect(page.getByRole('img', { name: 'Show only approved entries' })).not.toBeVisible();
});
