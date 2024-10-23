import { test, expect } from '@playwright/test';
import { checkViewOnFrontEnd, createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

test('Verify Update Button Text', async ({ page }, testInfo) => {
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'Event Registration', viewName: 'Verify Update Button Text Test', template: templates[0] });

    await page.locator('#gravityview_settings div').getByRole('link', { name: 'Edit Entry' }).click();
    const customMessage = "Zap It with Newness";
    await page.getByLabel('Update Button Text').fill(customMessage);
    await publishView(page);
    await checkViewOnFrontEnd(page);
    await page.getByRole('link', { name: 'John Doe' }).click();
    await page.getByRole('link', { name: 'Edit Entry' }).click();
    await expect(page.getByRole('button', { name: customMessage })).toBeVisible();

});