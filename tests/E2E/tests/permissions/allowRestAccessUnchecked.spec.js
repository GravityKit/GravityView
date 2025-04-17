import { test, expect } from '@playwright/test';
import { createView, publishView, templates } from '../../helpers/test-helpers';

/**
 * Ensures REST API access is disabled when 'Allow REST Access' is unchecked.
 */
test('Verify Allow Rest Access Unchecked', async ({ page }) => {
	let currentUrl, params, viewId, apiUrl, response;

	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await test.step('Create a new View with the Favorite Color form', async () => {
		await createView(page, {
			formTitle: 'Favorite Color',
			viewName: 'Verify Allow Rest Access Unchecked Test',
			template: templates[0]
		});
	});

	await test.step('Publish the View with the Allow REST Access setting unchecked', async () => {
		await publishView(page);
	});

	await test.step('Generate the API URL for the View', async () => {
		currentUrl = page.url();
		params = new URLSearchParams(new URL(currentUrl).search);
		viewId = params.get('post');
		apiUrl = `/wp-json/gravityview/v1/views/${viewId}`;
	});

	let responsePromise;
	await test.step('Wait for the REST API response and visit the API URL', async () => {
		responsePromise = page.waitForResponse(apiUrl);
		await page.goto(apiUrl);
	});

	await test.step('Verify that the REST API request is unsuccessful', async () => {
		response = await responsePromise;
		expect(response.ok()).toBe(false);
	});
});
