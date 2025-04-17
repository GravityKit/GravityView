import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../../helpers/test-helpers';

/**
 * Verify that the Is Starred Search Field filters and displays the correct entry.
 */
test('Entry Starred', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(page, {
		formTitle: 'A Simple Form',
		viewName: 'Entry Starred Test',
		template: templates[0]
	});
	await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
	await page
		.getByRole('cell', { name: 'Search Everything' })
		.getByRole('combobox')
		.selectOption('is_starred');
	await page.locator('.ui-dialog').getByRole('button', { name: 'Close', exact: true }).click();
	await publishView(page);
	await checkViewOnFrontEnd(page);
	await page.getByLabel('Is Starred').setChecked(true);
	await page.getByRole('button', { name: 'Search' }).click();
	const david = page.getByRole('cell', { name: 'David', exact: true });
	const charlie = page.getByRole('cell', { name: 'Charlie', exact: true });
	await expect(david).toBeVisible();
	await expect(charlie).not.toBeVisible();
});
