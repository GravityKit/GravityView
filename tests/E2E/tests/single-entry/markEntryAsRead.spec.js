import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Confirms an entry can be marked as "read" with correct status display.
 */
test('Verify Mark Entry As Read Setting', async ({ page }) => {
	const username = 'Alice Smith';
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(page, {
		formTitle: 'User Details',
		viewName: 'Verify Mark Entry As Read Setting Test',
		template: templates[0]
	});
	await publishView(page);
	await checkViewOnFrontEnd(page);
	await page.getByRole('link', { name: username }).click();
	const href = await page.getAttribute(
		'#wp-admin-bar-gravityview a[href*="gf_edit_forms"]',
		'href'
	);
	const formId = href.split('id=')[1];
	await page.goto(`/wp-admin/admin.php?page=gf_entries&id=${formId}`);
	const row = page.locator('#the-list tr', { hasText: username });
	const markUnread = row.locator('a:has-text("Mark unread")');
	await expect(markUnread).toBeVisible();
});
