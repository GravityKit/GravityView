import { test, expect } from '@playwright/test';
import { checkViewOnFrontEnd, createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

test('Verify Sort Direction ASC', async ({ page }, testInfo) => {
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'Favorite Color', viewName: 'Verify Sort Direction ASC Test', template: templates[0] });
    await page.locator('#gravityview_settings div').getByRole('link', { name: 'Filter & Sort' }).click();
    await page.locator('#gravityview_sort_field_1').selectOption({ label: 'Favorite Color' });
    await publishView(page);
    await checkViewOnFrontEnd(page);
    const cell = page.locator(`${templates[0].contains} tr:nth-child(1) td:nth-child(2)`);
    await expect(cell).toHaveText('Amber');
});