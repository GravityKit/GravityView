import { test, expect } from '@playwright/test';
import { createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

/**
 * Confirms direct URL access to the View is blocked when permissions restrict it.
 */
test('Verify Prevent Direct Access', async ({ browser }, testInfo) => {
    const loggedInContext = await browser.newContext();
    const page = await loggedInContext.newPage();
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'Favorite Color', viewName: 'Verify Prevent Direct Access Test', template: templates[0] });
    await page.locator('#gravityview_settings div').getByRole('link', { name: 'Permissions' }).click();
    await page.getByLabel('Prevent Direct Access').setChecked(true);
    const viewUrl = await page.locator('span#sample-permalink a').getAttribute('href');
    await publishView(page);
    const loggedOutContext = await browser.newContext({ storageState: {} });
    const loggedOutPage = await loggedOutContext.newPage();
    await loggedOutPage.goto(viewUrl);
    const body = loggedOutPage.locator('body');
    await expect(body).toContainText('You are not allowed to view this content.');
    await loggedOutContext.close();
    await loggedInContext.close();
});