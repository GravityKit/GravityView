import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	clickAddSearchField,
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
	await clickAddSearchField(page);
	const isReadField = page.locator('.ui-tooltip-content [data-fieldid="is_read"]');
	await expect(isReadField).toBeVisible();
	await isReadField.click();
	await page.getByRole('button', { name: ' Close' }).click();
	await publishView(page);
	await checkViewOnFrontEnd(page);
	await page.getByLabel('Is Read').selectOption({ label: 'Read', exact: true });
	await page.getByRole('button', { name: 'Search' }).click();
	const charlie = page.getByRole('cell', { name: 'Charlie', exact: true });
	const bob = page.getByRole('cell', { name: 'Bob', exact: true });
	await expect(charlie).toBeVisible();
	await expect(bob).not.toBeVisible();
});
