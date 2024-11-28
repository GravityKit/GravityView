import { test, expect } from '@playwright/test';
import { checkViewOnFrontEnd, createView, gotoAndEnsureLoggedIn, publishView, templates } from '../../helpers/test-helpers';

test('Secondary Sort Direction DESC', async ({ page }, testInfo) => {
    await gotoAndEnsureLoggedIn(page, testInfo);
    await createView(page, { formTitle: 'Pet Preference', viewName: 'Secondary Sort Direction DESC Test', template: templates[0] });
    await page.locator('#gravityview_settings div').getByRole('link', { name: 'Filter & Sort' }).click();
    await page.locator('#gravityview_sort_field_1').selectOption({ label: 'Preferred Pet' });
    await page.locator('#gravityview_sort_field_2').selectOption({ label: 'Name' });
    await page.locator('#gravityview_se_sort_direction_2').selectOption({ label: 'DESC' });
    await publishView(page);
    await checkViewOnFrontEnd(page);
    const firstCell = page.locator(`${templates[0].contains} tr:first-child td:first-child`);
    await expect(firstCell).toContainText('Ursula')
});