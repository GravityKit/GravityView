import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Checks that the lightbox functionality for images works as expected.
 */
test('Verify Lightbox Functionality for Images', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(page, {
		formTitle: 'Weather Form',
		viewName: 'Image Lightbox Test',
		template: templates[0]
	});
	await page.getByLabel('Show only approved entries').setChecked(false);
	await publishView(page);
	await checkViewOnFrontEnd(page);
	const imageLocator = page.locator('.gv-container .gravityview-fancybox').first();
	await expect(imageLocator).toBeVisible();
	await imageLocator.click();
	await expect(page.locator('.fancybox__container')).toBeVisible();
});
