import { test, expect } from '@playwright/test';
import { checkViewOnFrontEnd, createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

test('Verify No Entries Behavior', async ({ page }, testInfo) => {
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'No Entries', viewName: 'Verify No Entries Behavior Test', template: templates[0] });
    await publishView(page);
    await checkViewOnFrontEnd(page);
    await expect(page.getByText('No entries match your request.')).toBeVisible();
});