import { test, expect } from '@playwright/test';
import { checkViewOnFrontEnd, createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

test('Verify Custom JS', async ({ page }, testInfo) => {
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'Event Registration', viewName: 'Verify Custom JS Test', template: templates[0] });
    await page.locator('#gravityview_settings div').getByRole('link', { name: 'Custom Code' }).click();
    await page.fill('#gravityview_advanced tbody tr:nth-of-type(2) .CodeMirror-activeline', 'document.body.setAttribute("data-test", "custom-js-applied");');
    await publishView(page);
    await checkViewOnFrontEnd(page);
    const dataTestAttribute = await page.evaluate(() => {
        return document.body.getAttribute('data-test');
    });
    expect(dataTestAttribute).toBe('custom-js-applied');
});