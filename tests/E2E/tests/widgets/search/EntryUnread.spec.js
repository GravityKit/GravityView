import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../../helpers/test-helpers';

/**
 * Verify that the Is Unread Search Field filters and displays the correct entries.
 */
test('Entry Unread', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(page, {
		formTitle: 'A Simple Form',
		viewName: 'Entry Unread Test',
		template: templates[0]
	});
	await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
	const addSearchFieldButton = page
			.locator('#search-search-general-fields')
			.getByRole('link', { name: ' Add Search Field' });
	await expect (addSearchFieldButton).toBeVisible();
	await addSearchFieldButton.click();
	await page.locator('.ui-tooltip-content [data-fieldid="is_read"]').click();
	await page.getByRole('button', { name: ' Close' }).click();
	await publishView(page);
	await checkViewOnFrontEnd(page);
	await page.getByLabel('Is Read').selectOption({ label: 'Unread', exact: true });
	await page.getByRole('button', { name: 'Search' }).click();
	const alice = page.getByRole('cell', { name: 'Alice', exact: true });
	const bob = page.getByRole('cell', { name: 'Bob', exact: true });
	const charlie = page.getByRole('cell', { name: 'Charlie', exact: true });
	const david = page.getByRole('cell', { name: 'David', exact: true });
	await expect(alice).toBeVisible();
	await expect(bob).toBeVisible();
	await expect(charlie).not.toBeVisible();
	await expect(david).toBeVisible();
});
