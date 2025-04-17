import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { createView, publishView, templates } from '../../helpers/test-helpers';

/**
 * Tests the process of creating a new GravityView View.
 */
test.describe.serial('GravityView View Creation', () => {
	let firstTestSkipped = true;

	test('Create a new GravityView view', async ({ page }, testInfo) => {
		await page.goto('/wp-admin/edit.php?post_type=gravityview');
		await createView(
			page,
			{
				formTitle: 'A Simple Form',
				viewName: 'Test View',
				template: templates[0]
			},
			testInfo
		);
		firstTestSkipped = false;
		await publishView(page);
	});

	test('Add fields to a GravityView View', async ({ page }, testInfo) => {
		testInfo.skip(firstTestSkipped, 'Skipping test because the first test was skipped.');
		await page.goto('/wp-admin/edit.php?post_type=gravityview');
		const viewSelector = 'a.row-title:has-text("Test View")';
		await page.click(viewSelector);

		await Promise.race([
			page.waitForSelector('#gravityview_select_template', {
				state: 'visible'
			}),
			page.waitForSelector('#gv-view-configuration-tabs', {
				state: 'visible'
			})
		]);

		if (await page.isVisible('#gravityview_select_template')) {
			await page.waitForSelector('.gv-view-types-module', {
				state: 'visible'
			});
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

			await publishView(page);
		}

		await page.waitForSelector('.gv-fields');
		await page.click('#directory-active-fields a.gv-add-field');

		const fieldSelector = '.gravityview-item-picker-tooltip';
		await page.waitForSelector(fieldSelector, { state: 'visible' });

		const addFieldButton =
			'.gravityview-item-picker-tooltip div[data-fieldid="date_created"] .gv-add-field';
		await page.waitForSelector(addFieldButton, { state: 'visible' });

		await page.click(
			'.gravityview-item-picker-tooltip .gv-items-picker-container > div[data-fieldid="date_created"]'
		);

		const addedFieldSelector = page
			.locator('#directory-active-fields')
			.getByTitle('Field: Date Created\nThe date the entry was created.\nForm ID:')
			.locator('span');

		await expect(addedFieldSelector).toBeVisible();
	});
});
