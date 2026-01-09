import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Verifies accurate redirection after editing an entry.
 */
test('Verify Redirect After Editing', async ({ page }, testInfo) => {
	let singleEntryURL;

	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await test.step('Create a new View with the Event Registration form', async () => {
		await createView(page, {
			formTitle: 'Event Registration',
			viewName: 'Verify Redirect After Editing Test',
			template: templates[0]
		}, testInfo);
	});

	await test.step('Set redirection after editing to "Redirect to Single Entry" and publish the View', async () => {
		await page
			.locator('#gravityview_settings div')
			.getByRole('link', { name: 'Edit Entry' })
			.click();
		await page
			.getByLabel('Redirect After Editing')
			.selectOption({ label: 'Redirect to Single Entry' });
		await publishView(page);
	});

	await test.step('Check the View on the front end', async () => {
		await checkViewOnFrontEnd(page);
	});

	await test.step('Verify redirection to the single entry page after editing', async () => {
		await page.getByRole('link', { name: 'John Doe' }).click();
		singleEntryURL = page.url();
		await page.getByRole('link', { name: 'Edit Entry' }).click();
		await page.getByRole('button', { name: 'Update' }).click();
		await page.waitForURL(singleEntryURL);
		expect(page.url()).toBe(singleEntryURL);
	});
});
