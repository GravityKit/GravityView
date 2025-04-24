import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

test('Verify Custom CSS is applied on front end', async ({ page }, testInfo) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await test.step('Create a new View with a predefined template', async () => {
		await createView(
			page,
			{
				formTitle: 'Event Registration',
				viewName: 'Verify Custom CSS Test',
				template: templates[0]
			},
			testInfo
		);
	});

	await test.step('Navigate to the "Custom Code" settings tab and add custom CSS', async () => {
		const customCodeLink = page
			.locator('#gravityview_settings div')
			.getByRole('link', { name: 'Custom Code' });
		await customCodeLink.click();

		const codeEditor = page.locator(
			'#gravityview_advanced tbody tr:first-of-type .CodeMirror-activeline'
		);
		await codeEditor.fill('body { background-color: #ffeb3b; }');
	});

	await test.step('Publish the View', async () => {
		await publishView(page);
	});

	await test.step('Verify custom CSS is applied on the front end', async () => {
		await checkViewOnFrontEnd(page);

		const backgroundColor = await page.evaluate(
			() => window.getComputedStyle(document.body).backgroundColor
		);
		expect(backgroundColor).toBe('rgb(255, 235, 59)');
	});
});
