import { test, expect } from '@playwright/test';
import { checkViewOnFrontEnd, createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

/**
 * Verifies that custom CSS is applied correctly on the front end.
 */
test('Verify Custom CSS', async ({ page }, testInfo) => {
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'Event Registration', viewName: 'Verify Custom CSS Test', template: templates[0] });
    await page.locator('#gravityview_settings div').getByRole('link', { name: 'Custom Code' }).click();
    await page.fill('#gravityview_advanced tbody tr:first-of-type .CodeMirror-activeline', 'body { background-color: #ffeb3b; }');
    await publishView(page);
    await checkViewOnFrontEnd(page);
    const backgroundColor = await page.evaluate(() => {
        return window.getComputedStyle(document.body).backgroundColor;
    });
    expect(backgroundColor).toBe('rgb(255, 235, 59)');
});