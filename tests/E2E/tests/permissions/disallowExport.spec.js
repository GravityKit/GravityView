import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	clickDownloadButton,
	createView,
	getViewUrl,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Confirms entry downloads don’t work when 'Allow Export' setting is disabled.
 */
test('Verify Disallow Export', async ({ page }) => {
	let noDownload = true;

	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await test.step('Create a new View with the Favorite Color form', async () => {
		await createView(page, {
			formTitle: 'Favorite Color',
			viewName: 'Verify Disallow Export Test',
			template: templates[0]
		});
	});

	await test.step('Publish the View with the Allow Export setting disabled', async () => {
		await publishView(page);
	});

	const viewUrl = await getViewUrl(page);
	const downloadUrl = `${viewUrl}csv/`;

	await test.step('Attempt to download entries with export disabled', async () => {
		await checkViewOnFrontEnd(page);
		page.on('download', () => {
			noDownload = false;
		});
		await clickDownloadButton(page, downloadUrl);
	});

	await test.step('Verify no download happens when Allow Export is disabled', async () => {
		expect(noDownload).toBe(true);
	});
});
