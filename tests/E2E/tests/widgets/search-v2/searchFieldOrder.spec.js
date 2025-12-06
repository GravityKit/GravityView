const { test, expect } = require('@playwright/test');
const {
	createView,
	publishView,
	checkViewOnFrontEnd,
	viewTemplatesMap,
	clickFirstVisible,
	clickAddSearchField
} = require('../../../helpers/test-helpers');

/*
Verifies that the search widget fields appear in the correct order on the front end.
*/
test('Search Widget Field Order', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await createView(page, {
		formTitle: 'A Simple Form',
		viewName: 'Search Widget Order',
		template: viewTemplatesMap.table
	});

	await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
	await clickAddSearchField(page);
	await page
		.getByRole('tooltip')
		.locator('.gv-field-label-text-container', { hasText: 'Is Starred' })
		.click();
	await page
		.getByRole('tooltip')
		.locator('.gv-field-label-text-container', { hasText: 'Approval Status' })
		.click();
	await clickFirstVisible(page, page.getByRole('button', { name: /Close/ }));

	await publishView(page);
	await checkViewOnFrontEnd(page);

	const searchFieldsContainer = page.locator('form.gv-widget-search');
	const rawFieldLabels = await searchFieldsContainer
		.locator('label, span, legend')
		.allTextContents();
	const fieldLabels = rawFieldLabels.map((label) => label.trim());
	const expectedOrder = ['Search Entries:', 'Is Starred', 'Approval:'];
	let lastIndex = -1;
	for (const label of expectedOrder) {
		const idx = fieldLabels.indexOf(label);
		expect(idx).toBeGreaterThan(lastIndex);
		lastIndex = idx;
	}
});
