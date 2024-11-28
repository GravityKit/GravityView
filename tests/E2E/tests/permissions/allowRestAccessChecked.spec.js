import { test, expect } from '@playwright/test';
import { createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

test('Verify Allow Rest Access Checked', async ({ page }, testInfo) => {
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'Favorite Color', viewName: 'Verify Allow Rest Access Checked Test', template: templates[0] });
    await page.locator('#gravityview_settings div').getByRole('link', { name: 'Permissions' }).click();
    await page.getByLabel('Allow REST Access').setChecked(true);
    await publishView(page);
    const currentUrl = page.url();
    const params = new URLSearchParams(new URL(currentUrl).search);
    const viewId = params.get('post');
    const baseUrl = new URL(currentUrl).origin;
    const apiUrl = `${baseUrl}/wp-json/gravityview/v1/views/${viewId}`;
    const responsePromise = page.waitForResponse(apiUrl);
    await page.goto(apiUrl);
    const response = await responsePromise;
    expect(response.ok()).toBe(true);
});