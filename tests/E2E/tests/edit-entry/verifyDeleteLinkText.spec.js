import { test, expect } from '@playwright/test';
import { checkViewOnFrontEnd, createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

/**
 * Validates the text of the delete link.
 */
test('Verify Delete Link Text', async ({ page }, testInfo) => {
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'Event Registration', viewName: 'Verify Delete Link Text Test', template: templates[0] });

    await page.locator('#gravityview_settings div').getByRole('link', { name: 'Edit Entry' }).click();
    const customMessage = "Erase This Drama!";
    await page.getByLabel('Delete Link Text').fill(customMessage);
    await publishView(page);
    await checkViewOnFrontEnd(page);
    await page.getByRole('link', { name: 'John Doe' }).click();
    await page.getByRole('link', { name: 'Edit Entry' }).click();
    await expect(page.getByRole('link', { name: customMessage })).toBeVisible();

});