import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	selectGravityFormByTitle,
	publishView,
	templates,
	checkViewOnFrontEnd
} from '../../helpers/test-helpers';

/**
 * Checks functionality of template selection in layout settings.
 */
test.describe('GravityView Template Selection', () => {
	const form = {
		filename: 'simple',
		title: 'A Simple Form'
	};

	for (const template of templates) {
		test(`Verify GravityView template: ${template.name}`, async ({ page }, testInfo) => {
			await page.goto('/wp-admin/edit.php?post_type=gravityview');

			await page.waitForSelector('text=AsNew View', { state: 'visible' });

			await page.click('text=New View');

			await selectGravityFormByTitle(page, form.title);

			await page.fill('#title', `Test View - ${template.name}`);

			page.waitForSelector('#gravityview_select_template', {
				state: 'visible'
			});

			await page.waitForSelector('.gv-view-types-module', {
				state: 'visible'
			});

			const templateSelector = await page.$(template.selector);

			const isPlaceholder = await templateSelector.evaluate((element) =>
				element.classList.contains('gv-view-template-placeholder')
			);

			testInfo.skip(isPlaceholder, `${template.name} template not found.`);

			const selectButtonLocator = page.locator(
				`a.gv_select_template[data-templateid="${template.slug}"]`
			);
			await templateSelector.hover();
			await page.dispatchEvent(template.selector, 'mouseenter');
			await selectButtonLocator.waitFor({ state: 'visible' });
			await selectButtonLocator.click();

			await page.waitForSelector('#gravityview_settings', { state: 'visible' });

			const checkbox = page.locator('#gravityview_se_show_only_approved');
			(await checkbox.isVisible()) && checkbox.uncheck();

			await publishView(page);
			await checkViewOnFrontEnd(page);

			const containerExists = await page.locator(template.container).isVisible();
			expect(containerExists).toBeTruthy();

			// Check below is simplified for List View since it has no default fields.
			if (template.contains && template.slug !== 'default_list') {
				const elementExists = await page
					.locator(`${template.container} ${template.contains}`)
					.isVisible();
				expect(elementExists).toBeTruthy();
			}
		});
	}
});
