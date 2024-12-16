import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	clickDownloadButton,
	createView,
	gotoAndEnsureLoggedIn,
	publishView,
	templates,
} from '../../helpers/test-helpers';

/**
 * Confirms entry downloads don’t work when 'Allow Export' setting is disabled.
 */
test('Verify Disallow Export', async ({ page }, testInfo) => {
	let noDownload = true;
	page.on('download', (download) => {
		noDownload = false;
	});
	await gotoAndEnsureLoggedIn(page, testInfo);
	await createView(page, {
		formTitle: 'Favorite Color',
		viewName: 'Verify Disallow Export Test',
		template: templates[0],
	});
	await publishView(page);
	const viewUrl = await page
		.locator('#sample-permalink a')
		.getAttribute('href');
	const downloadUrl = `${viewUrl}csv/`;
	await checkViewOnFrontEnd(page);
	await clickDownloadButton(page, downloadUrl);
	expect(noDownload).toBe(true);
});
