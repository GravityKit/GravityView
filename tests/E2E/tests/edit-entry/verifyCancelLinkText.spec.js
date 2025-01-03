import { test, expect } from '@playwright/test';
import { checkViewOnFrontEnd, createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

/**
 * Checks the correct display of the cancel link text.
 */
test('Verify Cancel Link Text', async ({ page }, testInfo) => {
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'Event Registration', viewName: 'Verify Cancel Link Text Test', template: templates[0] });

    await page.locator('#gravityview_settings div').getByRole('link', { name: 'Edit Entry' }).click();
    const customMessage = "Forget It, Jack";
    await page.getByLabel('Cancel Link Text').fill(customMessage);
    await publishView(page);
    await checkViewOnFrontEnd(page);
    await page.getByRole('link', { name: 'John Doe' }).click();
    await page.getByRole('link', { name: 'Edit Entry' }).click();
    await expect(page.getByRole('link', { name: customMessage })).toBeVisible();
});