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
 * - "Global search • Matches All" (just Search Everything, default mode)
 * - "Global search +2 fields • Matches All" (Search Everything + 2 additional fields)
 * - "3 fields • Matches Any" (no Search Everything, 3 fields, mode=any)
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

		// Add two search fields using data-fieldid selectors (proven pattern from other tests)
		await page
			.locator('#search-search-general-fields')
			.getByRole('link', { name: /Add Search Field/ })
			.click();
		await page.locator('.ui-tooltip-content [data-fieldid="is_starred"]').click();
		await page.locator('.ui-tooltip-content [data-fieldid="is_approved"]').click();

		await clickFirstVisible(page, page.getByRole('button', { name: /Close/ }));

		// Verify summary shows "Global search +2 fields" (default Search Everything + 2 added)
		// Default mode is "Matches All"
		const widgetCard = page.locator('[data-fieldid="search_bar"]');
		const headerSummary = widgetCard.locator('h5 .gv-field-info').first();
		await expect(headerSummary).toContainText('Global search +2 fields');
		await expect(headerSummary).toContainText('Matches All');
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
		await page.locator('.ui-tooltip-content [data-fieldid="is_starred"]').click();

		// Close the tooltip by pressing Escape, then verify the field was added
		await page.keyboard.press('Escape');

		// Verify the Is Starred field row exists before trying to remove it
		const isStarredRow = page.locator('.gv-search-field-row', { hasText: 'Is Starred' });
		await expect(isStarredRow).toBeVisible();

		await clickFirstVisible(page, page.getByRole('button', { name: /Close/ }));

		// Verify initial count
		const widgetCard = page.locator('[data-fieldid="search_bar"]');
		const headerSummary = widgetCard.locator('h5 .gv-field-info').first();
		await expect(headerSummary).toContainText('Global search +1 field');

		// Re-open and remove the Is Starred field
		await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
		await page
			.locator('.gv-search-field-row', { hasText: 'Is Starred' })
			.getByRole('button', { name: /Remove/ })
			.click();
		await clickFirstVisible(page, page.getByRole('button', { name: /Close/ }));

		// Verify summary updated to just show Global search
		await expect(headerSummary).toContainText('Global search');
		await expect(headerSummary).not.toContainText('+');
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

		// Add the Search Mode field using data-fieldid selector
		await page
			.locator('#search-search-general-fields')
			.getByRole('link', { name: /Add Search Field/ })
			.click();
		await page.locator('.ui-tooltip-content [data-fieldid="search_mode"]').click();

		// Close the tooltip
		await page.keyboard.press('Escape');

		// The Search Mode field settings should now be visible in the dialog
		// Wait for the mode select to be visible and change it to "any"
		const modeSelect = page.locator('select[name$="[mode]"]').first();
		await expect(modeSelect).toBeVisible();
		await modeSelect.selectOption('any');

		await clickFirstVisible(page, page.getByRole('button', { name: /Close/ }));

		// Verify summary includes "Matches Any" (changed from default "All")
		const widgetCard = page.locator('[data-fieldid="search_bar"]');
		const headerSummary = widgetCard.locator('h5 .gv-field-info').first();
		await expect(headerSummary).toContainText('Matches Any');
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

		// Find Search Bar in picker tooltip - target the specific label element
		const searchBarLabel = page.locator('.ui-tooltip-content .gv-field-label', {
			hasText: 'Search Bar'
		});

		// Verify picker shows the label (not the summary format)
		await expect(searchBarLabel).toBeVisible();

		// The picker should show the default description, not the dynamic summary
		const searchBarOption = page.locator('.ui-tooltip-content [data-fieldid="search_bar"]');
		await expect(searchBarOption).toBeVisible();
		// Check the info area doesn't contain summary text (should have default description)
		await expect(searchBarOption.locator('.gv-field-info')).not.toContainText('Global search');
		await expect(searchBarOption.locator('.gv-field-info')).not.toContainText('Matches');
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

		// Wait for dialog to be ready
		await page.waitForSelector('.gv-dialog-options');

		// Remove all fields - keep clicking remove until none are left
		const fieldRows = page.locator('.gv-search-field-row');
		const maxIterations = 10;
		let iterations = 0;
		while ((await fieldRows.count()) > 0 && iterations < maxIterations) {
			const currentCount = await fieldRows.count();
			await fieldRows.first().getByRole('button', { name: /Remove/ }).click();
			// Wait for the count to decrease (deterministic)
			await expect(fieldRows).toHaveCount(currentCount - 1);
			iterations++;
		}

		await clickFirstVisible(page, page.getByRole('button', { name: /Close/ }));

		// Verify summary shows warning
		const widgetCard = page.locator('[data-fieldid="search_bar"]');
		const headerSummary = widgetCard.locator('h5 .gv-field-info').first();
		await expect(headerSummary).toContainText('Needs configuration');
	});
});
