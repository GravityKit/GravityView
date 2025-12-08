import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Ensures primary sorting in ascending order is correctly applied.
 */
test('Verify Sort Direction ASC', async ({ page }) => {
	let cell;

	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await test.step('Create a new View with the Favorite Color form', async () => {
		await createView(page, {
			formTitle: 'Favorite Color',
			viewName: 'Verify Sort Direction ASC Test',
			template: templates[0]
		});
	});

	await test.step('Enable sorting by Favorite Color in ascending order', async () => {
		await page
			.locator('#gravityview_settings div')
			.getByRole('link', { name: 'Filter & Sort' })
			.click();
		const sortField = page.locator('#gravityview_sort_field_1');
		await expect(sortField).toBeEnabled();
		await sortField.selectOption({ label: 'Favorite Color' });
		await publishView(page);
	});

	await test.step('Check the View on the front end', async () => {
		await checkViewOnFrontEnd(page);
	});

	await test.step('Verify the first cell contains the color "Amber"', async () => {
		cell = page.locator(`${templates[0].contains} tr:nth-child(1) td:nth-child(2)`);
		await expect(cell).toHaveText('Amber');
	});
});
