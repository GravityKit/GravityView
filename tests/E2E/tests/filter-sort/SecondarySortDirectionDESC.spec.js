import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Verifies that secondary sorting in descending order works as expected.
 */
test('Secondary Sort Direction DESC', async ({ page }) => {
	let firstCell;

	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await test.step('Create a new View with the Pet Preference form', async () => {
		await createView(page, {
			formTitle: 'Pet Preference',
			viewName: 'Secondary Sort Direction DESC Test',
			template: templates[0]
		});
	});

	await test.step('Enable secondary sorting by Name in descending order', async () => {
		await page
			.locator('#gravityview_settings div')
			.getByRole('link', { name: 'Filter & Sort' })
			.click();
		const sortField = page.locator('#gravityview_sort_field_1');
		await expect(sortField).toBeEnabled();
		await sortField.selectOption({ label: 'Preferred Pet' });
		const sortField2 = page.locator('#gravityview_sort_field_2');
		await expect(sortField2).toBeEnabled();
		await sortField2.selectOption({ label: 'Name' });
		const sortDirection = page.locator('#gravityview_se_sort_direction_2');
		await expect(sortDirection).toBeEnabled();
		await sortDirection.selectOption({ label: 'DESC' });
		await publishView(page);
	});

	await test.step('Check the View on the front end', async () => {
		await checkViewOnFrontEnd(page);
	});

	await test.step('Verify the first cell contains the name "Ursula"', async () => {
		firstCell = page.locator(`${templates[0].contains} tr:first-child td:first-child`);
		await expect(firstCell).toContainText('Ursula');
	});
});
