import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Verifies that column sorting is enabled and functions correctly for the selected field.
 */
test('Verify Column Sorting', async ({ page }) => {
	let cell;

	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await test.step('Create a new View with the Favorite Color form', async () => {
		await createView(page, {
			formTitle: 'Favorite Color',
			viewName: 'Verify Column Sorting Test',
			template: templates[0]
		});
	});

	await test.step('Enable column sorting and select the Favorite Color field', async () => {
		await page
			.locator('#gravityview_settings div')
			.getByRole('link', { name: 'Filter & Sort' })
			.click();
		await page.getByLabel('Enable sorting by column').setChecked(true);
		await page.locator('#gravityview_sort_field_1').selectOption({ label: 'Favorite Color' });
		await publishView(page);
	});

	await test.step('Check the View on the front end', async () => {
		await checkViewOnFrontEnd(page);
	});

	await test.step('Sort by Favorite Color and verify the first row', async () => {
		await page.click('th[data-label="Favorite Color"] .gv-sort');
		cell = page.locator(`${templates[0].contains} tr:nth-child(1) td:nth-child(2)`);
		await expect(cell).toHaveText('Yellow');
	});
});
