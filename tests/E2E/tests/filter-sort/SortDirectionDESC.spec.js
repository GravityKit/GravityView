import { test, expect } from '@playwright/test';
import { checkViewOnFrontEnd, createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

test('Verify Sort Direction DESC', async ({ page }, testInfo) => {
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'Favorite Color', viewName: 'Verify Sort Direction DESC Test', template: templates[0] });
    await page.locator('#gravityview_settings div').getByRole('link', { name: 'Filter & Sort' }).click();
    await page.locator('#gravityview_se_sort_direction').selectOption({ label: 'DESC' });
    await page.locator('#gravityview_sort_field_1').selectOption({ label: 'Favorite Color' });
    await publishView(page);
    await checkViewOnFrontEnd(page);
    const cell = page.locator(`${templates[0].contains} tr:nth-child(1) td:nth-child(2)`);
    await expect(cell).toHaveText('Yellow');
});