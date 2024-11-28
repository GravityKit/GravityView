import { test, expect } from '@playwright/test';
import { checkViewOnFrontEnd, clickDownloadButton, createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

test('Verify Allow Export', async ({ page }, testInfo) => {

    let success = false;

    page.on('download',
        download => {
            success = true;
        }
    );
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'Favorite Color', viewName: 'Verify Allow Export Test', template: templates[0] });
    await page.locator('#gravityview_settings div').getByRole('link', { name: 'Permissions' }).click();
    await page.getByLabel('Allow Export').setChecked(true);
    await publishView(page);
    const viewUrl = await page.locator('#sample-permalink a').getAttribute('href');
    const downloadUrl = `${viewUrl}csv/`;
    await checkViewOnFrontEnd(page);
    await clickDownloadButton(page, downloadUrl);
    expect(success).toBe(true);

});