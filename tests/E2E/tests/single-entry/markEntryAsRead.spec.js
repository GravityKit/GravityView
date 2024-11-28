import { test, expect } from '@playwright/test';
import { checkViewOnFrontEnd, createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

test('Verify Mark Entry As Read Setting', async ({ page }, testInfo) => {
    const username = 'Alice Smith';
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'User Details', viewName: 'Verify Mark Entry As Read Setting Test', template: templates[0] });
    await publishView(page);
    await checkViewOnFrontEnd(page);
    await page.getByRole('link', { name: username }).click();
    const href = await page.getAttribute('#wp-admin-bar-gravityview a[href*="gf_edit_forms"]', 'href');
    const formId = href.split('id=')[1];
    const currentUrl = page.url();
    const baseUrl = new URL(currentUrl).origin;
    await page.goto(`${baseUrl}/wp-admin/admin.php?page=gf_entries&id=${formId}`);

    const row = page.locator('#the-list tr', { hasText: username });
    const markUnread = row.locator('a:has-text("Mark unread")');
    await expect(markUnread).toBeVisible();
});