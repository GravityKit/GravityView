import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../../helpers/test-helpers';

/**
 * Verify that the Is Read Search Field filters and displays the correct entry.
 */
test('Entry Read', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(page, {
		formTitle: 'A Simple Form',
		viewName: 'Entry Read Test',
		template: templates[0]
	});
	await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
	await page
		.getByRole('cell', { name: 'Search Everything' })
		.getByRole('combobox')
		.selectOption('is_read');
	await page.locator('.ui-dialog').getByRole('button', { name: 'Close', exact: true }).click();
	await publishView(page);
	await checkViewOnFrontEnd(page);
	await page.getByLabel('Is Read').selectOption({ label: 'Read', exact: true });
	await page.getByRole('button', { name: 'Search' }).click();
	const charlie = page.getByRole('cell', { name: 'Charlie', exact: true });
	const bob = page.getByRole('cell', { name: 'Bob', exact: true });
	await expect(charlie).toBeVisible();
	await expect(bob).not.toBeVisible();
});
