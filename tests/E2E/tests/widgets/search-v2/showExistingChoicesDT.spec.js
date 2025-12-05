const { test, expect } = require('@playwright/test');
const {
	createView,
	publishView,
	checkViewOnFrontEnd,
	viewTemplatesMap,
	clickFirstVisible
} = require('../../../helpers/test-helpers.js');

/*
Verifies that the search bar only displays dropdown choices that exist in submitted
entries for each field while ensuring that fields not configured to only show existing
choices are not affected.
*/
test('Search Bar Only Shows Choices That Exist in Submitted Entries', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await test.step('Create a new View', async () => {
		await createView(page, {
			formTitle: 'Productivity Tracker',
			viewName: 'Displays Only Choices That Exist in Submitted Entries',
			template: viewTemplatesMap.dataTables
		});
	});

	await test.step('Configure Search Bar and add fields', async () => {
		await page.getByRole('button', { name: 'Configure Search Bar Settings' }).click();
		const addSearchFieldButton = page
			.locator('#search-search-general-fields')
			.getByRole('link', { name: ' Add Search Field' });
		await expect (addSearchFieldButton).toBeVisible();
		await addSearchFieldButton.click();
		const filterFields = page
			.getByRole('tooltip', { name: 'Close  Filter Fields: Filter' })
			.getByPlaceholder('Filter fields by name or label');
		await filterFields.fill('productivity');
		await filterFields.press('Enter');
		const tooltip = page
			.getByRole('tooltip', { name: 'Close  Filter Fields: Filter' })
			.first();
		await expect(tooltip).toBeVisible();

		await clickFirstVisible(
			page,
			tooltip.getByTitle(
				'Search Field: What is your primary productivity tool?\nGravity Forms Field'
			)
		);
		await clickFirstVisible(
			page,
			tooltip.getByText('How do you measure your productivity?', { exact: true })
		);
		await clickFirstVisible(
			page,
			tooltip.getByText('What is your biggest productivity challenge?', { exact: true })
		);
	});

	await test.step('Configure "Only show choices that exist" for 2/3 fields', async () => {
		await page
			.getByRole('heading', {
				name: 'Configure Settings What is your primary productivity tool?  Remove'
			})
			.getByLabel('Configure Settings')
			.click();
		await clickFirstVisible(page, page.getByText('Only show choices that exist'));
		await page.locator('button[data-close-settings]').click();

		await page
			.getByRole('heading', { name: 'Configure Settings How do you' })
			.getByLabel('Configure Settings')
			.click();
		await clickFirstVisible(page, page.getByText('Only show choices that exist'));
		await page.locator('button[data-close-settings]').click();

		await page
			.getByLabel('Search Bar Settings', { exact: true })
			.getByRole('button', { name: 'Close', exact: true })
			.click();
	});

	await test.step('Publish the View and check it on the frontend', async () => {
		await publishView(page);
		await checkViewOnFrontEnd(page);
	});

	await test.step('Assert search fields are visible', async () => {
		await expect(page.getByRole('combobox', { name: 'What is your primary productivity tool?' })).toBeVisible();
		await expect(page.getByRole('combobox', { name: 'How do you measure your productivity?' })).toBeVisible();
		await expect(page.getByRole('combobox', { name: 'What is your biggest productivity challenge?' })).toBeVisible();
	});

	await test.step('Assert dropdown choices for each field', async () => {
		const select1 = page.getByRole('combobox', { name: 'What is your primary productivity tool?' });
		const options1 = await select1.locator('option').allTextContents();
		expect(options1).toContain('Asana');
		expect(options1).toContain('Trello');
		expect(options1).not.toContain('Basecamp');

		const select2 = page.getByRole('combobox', { name: 'How do you measure your productivity?' });
		const options2 = await select2.locator('option').allTextContents();
		expect(options2).toContain('Weekly Reviews');
		expect(options2).toContain('Time Tracking');
		expect(options2).not.toContain('Pomodoro Technique');

		// Nothing should be filtered/sieved out from this field
		const select3 = page.getByRole('combobox', { name: 'What is your biggest productivity challenge?' });
		const options3 = await select3.locator('option').allTextContents();
		expect(options3).toContain('Distractions');
		expect(options3).toContain('Overwhelm');
		expect(options3).toContain('Task Prioritization');
	});
});
