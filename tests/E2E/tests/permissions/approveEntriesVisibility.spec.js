const { test, expect } = require('@playwright/test');
const {
	createView,
	publishView,
	viewTemplatesMap,
	loginAsSubscriber,
	getViewUrl
} = require('../../helpers/test-helpers');

test.describe('Entry Approval Visibility', () => {
	test('should restrict approve entries field visibility to admin users only', async ({ page, browser }) => {
		await page.goto('/wp-admin/edit.php?post_type=gravityview');

		await createView(page, {
			formTitle: 'Pet Preference',
			viewName: 'Approve Entries Visibility Test',
			template: viewTemplatesMap.table
		});

		await page.getByRole('link', { name: ' Add Table Column' }).click();
		await page.locator('#ui-id-15').getByText('Add "Link to Edit Entry" Link').click();
		
        await page.getByRole('link', { name: '   Edit Entry', exact: true }).click();
        await page.getByLabel('Enable Edit Locking Learn').uncheck();
		await page.getByLabel('Allow User Edit Learn').check();
		await page.getByLabel('Unapprove Entries After Edit').check();

		await page.getByRole('link', { name: ' Edit Entry Layout' }).click();
		await page.getByRole('link', { name: ' Add Field' }).click();
		await page.locator('#ui-id-16').getByText('Name', { exact: true }).click();

        await page.locator('#ui-id-16').getByText('Add "Approve Entries" Approve').click();
        await page.getByRole('button', { name: 'Configure Approve Entries' }).click();
        await page.getByLabel('Approve Entries Settings', { exact: true }).getByLabel('Make field editable to: Entry').selectOption('manage_options');
        await page.getByLabel('Approve Entries Settings', { exact: true }).getByRole('button', { name: 'Close', exact: true }).click();

		await publishView(page);
		const viewUrl = await getViewUrl(page);

		const subscriberContext = await loginAsSubscriber(browser);
		const subscriberPage = await subscriberContext.newPage();

		await subscriberPage.goto(viewUrl);
        await subscriberPage.getByRole('link', { name: 'Edit Entry' }).click();

        // Verify subscribers can't see the Approve Entries field
        expect(subscriberPage.getByText('Edit Entry')).toBeVisible();
        expect(subscriberPage.getByText('Approve Entries', { exact: true })).not.toBeVisible();

		await subscriberContext.close();
	});
});