import { test, expect } from '@playwright/test';
import { createView, getViewUrl, publishView, templates } from '../../helpers/test-helpers';

/**
 * Confirms direct URL access to the View is blocked when permissions restrict it.
 */
test('Verify Prevent Direct Access', async ({ browser }) => {
	const loggedInContext = await browser.newContext();
	const page = await loggedInContext.newPage();

	await test.step('Create the View', async () => {
		await page.goto('/wp-admin/edit.php?post_type=gravityview');
		await createView(page, {
			formTitle: 'Favorite Color',
			viewName: 'Verify Prevent Direct Access Test',
			template: templates[0]
		});
	});

	await test.step('Enable Prevent Direct Access setting', async () => {
		await page
			.locator('#gravityview_settings div')
			.getByRole('link', { name: 'Permissions' })
			.click();
		await page.getByLabel('Prevent Direct Access').setChecked(true);
	});

	const viewUrl = await getViewUrl(page);
	await publishView(page);

	const loggedOutContext = await browser.newContext({ storageState: {} });
	const loggedOutPage = await loggedOutContext.newPage();

	await test.step('Attempt direct URL access while logged out', async () => {
		await loggedOutPage.goto(viewUrl);
		const body = loggedOutPage.locator('body');
		await expect(body).toContainText('You are not allowed to view this content.');
	});

	await loggedOutContext.close();
	await loggedInContext.close();
});
