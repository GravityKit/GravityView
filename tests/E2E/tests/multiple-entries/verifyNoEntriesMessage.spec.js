import { test, expect } from '@playwright/test';
import { checkViewOnFrontEnd, createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

/**
 * Checks for the appropriate message when no entries exist.
 */
test('Verify No Entries Custom Message', async ({ page }, testInfo) => {
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'No Entries', viewName: 'Verify No Entries Message Test', template: templates[0] });

    await page.locator('#gravityview_settings div').getByRole('link', { name: 'Multiple Entries' }).click();
    const customMessage = "Empty! Even the tumbleweeds are bored.";
    await page.getByPlaceholder('No entries match your request.').fill(customMessage);

    await publishView(page);
    await checkViewOnFrontEnd(page);
    await expect(page.getByText(customMessage)).toBeVisible();
});