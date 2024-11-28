import { test, expect } from '@playwright/test';
import { checkViewOnFrontEnd, createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

test('Verify Redirect After Editing', async ({ page }, testInfo) => {
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'Event Registration', viewName: 'Verify Redirect After Editing Test', template: templates[0] });

    await page.locator('#gravityview_settings div').getByRole('link', { name: 'Edit Entry' }).click();
    await page.getByLabel('Redirect After Editing').selectOption({ label: 'Redirect to Single Entry' });
    await publishView(page);
    await checkViewOnFrontEnd(page);
    
    await page.getByRole('link', { name: 'John Doe' }).click();
    const singleEntryURL = page.url();
    await page.getByRole('link', { name: 'Edit Entry' }).click();
    await page.getByRole('button', { name: 'Update' }).click();
    await page.waitForURL(singleEntryURL);
    expect(page.url()).toBe(singleEntryURL);
});