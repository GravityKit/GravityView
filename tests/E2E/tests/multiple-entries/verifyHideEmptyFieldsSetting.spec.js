import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Validates the effect of hiding empty fields on the view.
 */
test('Verify Hide Empty Fields Setting', async ({ page }, testInfo) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await createView(
		page,
		{
			formTitle: 'Favorite Book',
			viewName: 'Hide Empty Fields Setting Test',
			template: templates[1]
		},
		testInfo
	);

	const addFieldButton = '.gv-droppable-area[data-areaid="directory_list-title"] .gv-add-field';

	await page.click(addFieldButton);

	const fieldPickerTooltip = '.gravityview-item-picker-tooltip';
	await page.waitForSelector(fieldPickerTooltip, { state: 'visible' });

	await page
		.locator('.gravityview-item-picker-tooltip')
		.getByText('Favorite Book', { exact: true })
		.click();
	await page.getByRole('button', { name: 'Configure Favorite Book' }).click();
	await page
		.getByLabel('Favorite Book Settings', { exact: true })
		.getByLabel('Show Label')
		.setChecked(true);
	await page
		.getByLabel('Favorite Book Settings', { exact: true })
		.getByRole('button', { name: 'Close', exact: true })
		.click();

	await publishView(page);
	await checkViewOnFrontEnd(page);

	const listItems = page.locator(`${templates[1].container} .gv-list-view`);
	const totalElements = await listItems.count();
	const h3Count = await listItems.locator('h3').count();
	expect(h3Count).toBeLessThan(totalElements);
});
