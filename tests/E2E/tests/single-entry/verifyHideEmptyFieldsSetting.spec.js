import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Verifies empty fields are hidden with 'Hide Empty Fields' enabled.
 */
test('Verify Hide Empty Fields Setting (Single)', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(page, {
		formTitle: 'Favorite Book',
		viewName: 'Hide Empty Fields Setting Test (Single)',
		template: templates[1]
	});

	const addFieldButton = '.gv-droppable-area[data-areaid="directory_list-title"] .gv-add-field';
	await page.click(addFieldButton);
	await page.waitForTimeout(1000); // wait for the tooltip to appear
	const fieldPickerTooltip = '.gravityview-item-picker-tooltip';
	await page.waitForSelector(fieldPickerTooltip, { state: 'visible' });
	await page
		.locator('.gravityview-item-picker-tooltip')
		.getByText('Name', { exact: true })
		.click();
	await page.getByRole('button', { name: 'Configure Name' }).click();
	await page.getByLabel('Link to single entry', { exact: true }).setChecked(true);
	await page
		.getByLabel('Name Settings', { exact: true })
		.getByRole('button', { name: 'Close', exact: true })
		.click();

	await page.getByRole('link', { name: 'Single Entry Layout' }).click();
	const addFieldButtonSingle =
		'.gv-droppable-area[data-areaid="single_list-title"] .gv-add-field';
	await page.click(addFieldButtonSingle);
	await page
		.locator('.gravityview-item-picker-tooltip')
		.getByText('Favorite Book', { exact: true })
		.click();

	await publishView(page);
	await checkViewOnFrontEnd(page);
	await page.getByRole('link', { name: 'Charlie' }).click();
	await page.waitForURL(/.*entry.*/);
	const listItem = page.locator(`${templates[1].container} .gv-list-view`);
	await expect(listItem.locator('h1,h2,h3,h4,h5,h6')).toHaveCount(0);
});
