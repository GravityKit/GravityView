import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../../helpers/test-helpers';

/**
 * Verify that the Entry ID Search Field filters and displays the correct entry.
 */
test('Entry ID Field', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(page, {
		formTitle: 'A Simple Form',
		viewName: 'Entry ID Field Test',
		template: templates[0]
	});
	await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
	await page.getByRole('link', { name: 'Add Search Field' }).first().click();
	await page.locator('.ui-tooltip-content [data-fieldid="entry_id"]').click();
	await page.getByRole('button', { name: ' Close' }).click();
	await publishView(page);
	await checkViewOnFrontEnd(page);
	const link = page.getByRole('link', { name: 'Bob' });
	const url = await link.getAttribute('href');
	const entryIdMatch = url.match(/\/entry\/(\d+)\//);
	const entryId = entryIdMatch ? entryIdMatch[1] : null;
	if (!entryId) {
		throw new Error('Entry ID could not be extracted from the URL.');
	}
	await page.getByLabel('Entry ID').fill(entryId);
	await page.getByRole('button', { name: 'Search' }).click();
	const bob = page.getByRole('cell', { name: 'Bob', exact: true });
	const charlie = page.getByRole('cell', { name: 'Charlie', exact: true });
	await expect(bob).toBeVisible();
	await expect(charlie).not.toBeVisible();
});
