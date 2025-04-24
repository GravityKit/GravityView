import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Ensures various field types are shown on the multiple entries screen of a View.
 */
test('Verify All Fields Are Displayed Correctly', async ({ page }, testInfo) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(
		page,
		{
			formTitle: 'User Details',
			viewName: 'Verify All Fields Display',
			template: templates[0]
		},
		testInfo
	);
	await publishView(page);
	await checkViewOnFrontEnd(page);

	await expect(page.getByText('Alice Smith')).toBeVisible();
	await expect(page.getByText('Bob Johnson')).toBeVisible();

	await expect(page.getByText('alice@example.com')).toBeVisible();
	await expect(page.getByText('bob@example.com')).toBeVisible();

	await expect(page.getByText('35', { exact: true })).toBeVisible();
	await expect(page.getByText('45', { exact: true })).toBeVisible();

	await expect(page.getByText('06/15/1994', { exact: true })).toBeVisible();
	await expect(page.getByText('08/22/1979', { exact: true })).toBeVisible();

	await expect(page.getByText('Red', { exact: true })).toBeVisible();
	await expect(page.getByText('Blue', { exact: true })).toBeVisible();
});
