import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../../helpers/test-helpers';

/**
 * Verify that the Entry Date Search Field filters entries correctly based on the selected date range.
 */
test('Entry Date Field', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(page, {
		formTitle: 'A Simple Form',
		viewName: 'Entry Date Field Test',
		template: templates[0]
	});
	await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
	await page
		.getByRole('cell', { name: 'Search Everything' })
		.getByRole('combobox')
		.selectOption('entry_date');
	await page.locator('.ui-dialog').getByRole('button', { name: 'Close', exact: true }).click();
	await publishView(page);
	await checkViewOnFrontEnd(page);
	await page.getByPlaceholder('Start date').fill('04/01/2024');
	await page.getByPlaceholder('End date').fill('04/15/2024');
	await page.getByRole('button', { name: 'Search' }).click();
	const david = page.getByRole('cell', { name: 'David', exact: true });
	const charlie = page.getByRole('cell', { name: 'Charlie', exact: true });
	await expect(david).toBeVisible();
	await expect(charlie).not.toBeVisible();
});
