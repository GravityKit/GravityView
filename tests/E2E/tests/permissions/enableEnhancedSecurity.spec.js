import { test, expect } from '@playwright/test';
import {
	createPageWithShortcode,
	createView,
	gotoAndEnsureLoggedIn,
	publishView,
	templates,
} from '../../helpers/test-helpers';

/**
 * Ensures a View without a valid secret shows an error message.
 */
test('Verify Ehanced Security', async ({ page }, testInfo) => {
	await gotoAndEnsureLoggedIn(page, testInfo);
	await createView(page, {
		formTitle: 'Favorite Color',
		viewName: 'Verify Ehanced Security Test',
		template: templates[0],
	});
	await publishView(page);
	const currentUrl = page.url();
	const params = new URLSearchParams(new URL(currentUrl).search);
	const viewId = params.get('post');
	const url = await createPageWithShortcode(page, {
		shortcode: `gravityview id="${viewId}"`,
		title: 'Verify Ehanced Security Test Page',
	});
	await page.goto(url);
	const errorMessage = page.locator('text=Invalid View secret provided');
	await expect(errorMessage).toBeVisible();
});
