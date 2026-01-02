import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

test('Verify Custom JS is executed correctly on front end', async ({ page }, testInfo) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await test.step('Create a new View with a predefined template', async () => {
		await createView(
			page,
			{
				formTitle: 'Event Registration',
				viewName: 'Verify Custom JS Test',
				template: templates[0]
			},
			testInfo
		);
	});

	await test.step('Navigate to the "Custom Code" settings tab and add custom JavaScript', async () => {
		const customCodeLink = page
			.locator('#gravityview_settings div')
			.getByRole('link', { name: 'Custom Code' });
		await customCodeLink.click();

		const jsEditor = page.locator(
			'#gravityview_advanced tbody tr:nth-of-type(3) .CodeMirror-activeline'
		);
		await jsEditor.fill('document.body.setAttribute("data-test", "custom-js-applied");');
	});

	await test.step('Publish the View', async () => {
		await publishView(page);
	});

	await test.step('Verify custom JavaScript is executed correctly on the front end', async () => {
		await checkViewOnFrontEnd(page);

		const dataTestAttribute = await page.evaluate(() =>
			document.body.getAttribute('data-test')
		);
		expect(dataTestAttribute).toBe('custom-js-applied');
	});
});
