import { test, expect } from '@playwright/test';
import { checkViewOnFrontEnd, createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

/**
 * Verifies that column sorting is enabled and functions correctly for the selected field.
 */
test('Verify Column Sorting', async ({ page }, testInfo) => {
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'Favorite Color', viewName: 'Verify Column Sorting Test', template: templates[0] });
    await page.locator('#gravityview_settings div').getByRole('link', { name: 'Filter & Sort' }).click();
    await page.getByLabel('Enable sorting by column').setChecked(true);
    await page.locator('#gravityview_sort_field_1').selectOption({ label: 'Favorite Color' });
    await publishView(page);
    await checkViewOnFrontEnd(page);
    await page.click('th[data-label="Favorite Color"] .gv-sort');
    const cell = page.locator(`${templates[0].contains} tr:nth-child(1) td:nth-child(2)`);
    await expect(cell).toHaveText('Yellow');
});