import { test, expect } from '@playwright/test';
import { checkViewOnFrontEnd, createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

test('Verify No Search Results Custom Message', async ({ page }, testInfo) => {
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'No Entries', viewName: 'Verify No Search Results Message Test', template: templates[0] });

    await page.locator('#gravityview_settings div').getByRole('link', { name: 'Multiple Entries' }).click();
    const customMessage = "Empty! Did the entries vanish into thin air?";
    await page.getByPlaceholder('This search returned no').fill(customMessage);

    await publishView(page);
    await checkViewOnFrontEnd(page);

    await page.getByLabel('Search Entries:').fill('Something random, ya know?');
    await page.getByRole('button', { name: 'Search' }).click();
    await page.waitForURL(/.*\?gv_search.*/);
    await expect(page.getByText(customMessage)).toBeVisible();
});