const { test, expect } = require('@playwright/test');
const {
	createView,
	viewTemplatesMap,
	clickFirstVisible
} = require('../../../helpers/test-helpers');

/**
 * Tests for the Search Bar widget summary feature in the View editor.
 * The summary displays field count and search mode at a glance.
 *
 * Summary format examples:
 * - "Global search • Matches Any" (just Search Everything)
 * - "Global search +2 fields • Matches Any" (Search Everything + 2 additional fields)
 * - "3 fields • Matches All" (no Search Everything, 3 fields, mode=all)
 * - "⚠️ Needs configuration" (no fields configured)
 */

test.describe('Search Bar Widget Summary', () => {
	test('Displays correct field count after adding fields', async ({ page }) => {
		await page.goto('/wp-admin/edit.php?post_type=gravityview');
		await createView(page, {
			formTitle: 'A Simple Form',
			viewName: 'Search Bar Summary - Field Count',
			template: viewTemplatesMap.table
		});

		// Open Search Bar settings
		await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();

		// Add two search fields (in addition to the default Search Everything)
		await page
			.locator('#search-search-general-fields')
			.getByRole('link', { name: /Add Search Field/ })
			.click();
		await page
			.getByRole('tooltip')
			.locator('.gv-field-label-text-container', { hasText: 'Is Starred' })
			.click();
		await page
			.getByRole('tooltip')
			.locator('.gv-field-label-text-container', { hasText: 'Approval Status' })
			.click();

		await clickFirstVisible(page, page.getByRole('button', { name: /Close/ }));

		// Verify summary shows "Global search +2 fields" (default Search Everything + 2 added)
		const widgetCard = page.locator('[data-fieldid="search_bar"]');
		await expect(widgetCard.locator('.gv-field-info')).toContainText('Global search +2 fields');
		await expect(widgetCard.locator('.gv-field-info')).toContainText('Matches Any');
	});

	test('Summary updates when fields are removed', async ({ page }) => {
		await page.goto('/wp-admin/edit.php?post_type=gravityview');
		await createView(page, {
			formTitle: 'A Simple Form',
			viewName: 'Search Bar Summary - Field Removal',
			template: viewTemplatesMap.table
		});

		// Open Search Bar settings and add a field
		await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
		await page
			.locator('#search-search-general-fields')
			.getByRole('link', { name: /Add Search Field/ })
			.click();
		await page
			.getByRole('tooltip')
			.locator('.gv-field-label-text-container', { hasText: 'Is Starred' })
			.click();
		await clickFirstVisible(page, page.getByRole('button', { name: /Close/ }));

		// Verify initial count
		const widgetCard = page.locator('[data-fieldid="search_bar"]');
		await expect(widgetCard.locator('.gv-field-info')).toContainText('Global search +1 field');

		// Re-open and remove the Is Starred field
		await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
		await page
			.locator('.gv-search-field-row', { hasText: 'Is Starred' })
			.getByRole('button', { name: /Remove/ })
			.click();
		await clickFirstVisible(page, page.getByRole('button', { name: /Close/ }));

		// Verify summary updated to just show Global search
		await expect(widgetCard.locator('.gv-field-info')).toContainText('Global search');
		await expect(widgetCard.locator('.gv-field-info')).not.toContainText('+');
	});

	test('Summary shows search mode correctly', async ({ page }) => {
		await page.goto('/wp-admin/edit.php?post_type=gravityview');
		await createView(page, {
			formTitle: 'A Simple Form',
			viewName: 'Search Bar Summary - Search Mode',
			template: viewTemplatesMap.table
		});

		// Open Search Bar settings
		await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();

		// Add the Search Mode field to allow users to toggle between Any/All
		await page
			.locator('#search-search-general-fields')
			.getByRole('link', { name: /Add Search Field/ })
			.click();
		await page
			.getByRole('tooltip')
			.locator('.gv-field-label-text-container', { hasText: 'Search Mode' })
			.click();

		// Change the default search mode to "all" (Matches All)
		await page.locator('select[name$="[mode]"]').selectOption('all');

		await clickFirstVisible(page, page.getByRole('button', { name: /Close/ }));

		// Verify summary includes "Matches All"
		const widgetCard = page.locator('[data-fieldid="search_bar"]');
		await expect(widgetCard.locator('.gv-field-info')).toContainText('Matches All');
	});

	test('Picker tooltip shows default description, not summary', async ({ page }) => {
		await page.goto('/wp-admin/edit.php?post_type=gravityview');
		await createView(page, {
			formTitle: 'A Simple Form',
			viewName: 'Search Bar Summary - Picker Tooltip',
			template: viewTemplatesMap.table
		});

		// Open widget picker (without adding Search Bar yet)
		await page
			.locator('#directory-header-widgets')
			.getByRole('link', { name: /Add Widget/ })
			.first()
			.click();

		// Find Search Bar in picker tooltip
		const searchBarOption = page.locator('.ui-tooltip-content').getByText('Search Bar', {
			exact: false
		});

		// Verify picker shows description, not field count/summary
		await expect(searchBarOption).toBeVisible();
		await expect(searchBarOption).not.toContainText('Global search');
		await expect(searchBarOption).not.toContainText('Matches');
	});

	test('Summary shows warning when no fields configured', async ({ page }) => {
		await page.goto('/wp-admin/edit.php?post_type=gravityview');
		await createView(page, {
			formTitle: 'A Simple Form',
			viewName: 'Search Bar Summary - No Fields',
			template: viewTemplatesMap.table
		});

		// Open Search Bar settings
		await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();

		// Remove all default fields (Search Everything should be present by default)
		const searchEverythingRow = page.locator('.gv-search-field-row', { hasText: 'Search Everything' });
		if (await searchEverythingRow.count() > 0) {
			await searchEverythingRow.getByRole('button', { name: /Remove/ }).click();
		}

		// Remove any other fields that might be present
		while ((await page.locator('.gv-search-field-row').count()) > 0) {
			await page.locator('.gv-search-field-row').first().getByRole('button', { name: /Remove/ }).click();
		}

		await clickFirstVisible(page, page.getByRole('button', { name: /Close/ }));

		// Verify summary shows warning
		const widgetCard = page.locator('[data-fieldid="search_bar"]');
		await expect(widgetCard.locator('.gv-field-info')).toContainText('Needs configuration');
	});
});
