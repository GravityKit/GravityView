import { test, expect } from '@playwright/test';
import { checkViewOnFrontEnd, createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

/**
 * Verifies that custom JavaScript code is executed correctly and affects the behavior of the front-end view as intended.
 */
test('Verify Redirect After Deleting', async ({ page }, testInfo) => {
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'Redundant Favorite Color', viewName: 'Verify Redirect After Deleting Test', template: templates[0] });

    await page.locator('#gravityview_settings div').getByRole('link', { name: 'Delete Entry' }).click();
    await page.getByLabel('Redirect After Deleting').selectOption({ label: 'Redirect to URL' });
    const customURL = 'http://example.com/';
    await page.getByLabel('Delete Entry Redirect URL').fill(customURL);
    await publishView(page);
    await checkViewOnFrontEnd(page);

    await page.locator('.gv-table-view tbody tr:nth-child(1)').getByRole('link').click();
    await page.getByRole('link', { name: 'Edit Entry' }).click();
    page.on('dialog', dialog => dialog.accept());
    await page.getByRole('link', { name: 'Delete', exact: true }).click();
    await page.waitForURL(customURL);
    expect(page.url()).toBe(customURL);
});