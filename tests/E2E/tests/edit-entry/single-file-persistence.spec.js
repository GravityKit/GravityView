import { test, expect } from '@playwright/test';
import {
	createView,
	publishView,
	checkViewOnFrontEnd,
	templates,
	getTestImagePath
} from '../../helpers/test-helpers';

/**
 * Ensures that a newly uploaded file is not silently dropped after an initial validation error during entry editing.
 */
test('Single file persistence during entry edit validation', async ({ page }, testInfo) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	test.slow();
	await createView(page, {
		formTitle: 'Weather Form',
		viewName: 'Single File Persistence Test',
		template: templates[0]
	}, testInfo);

	await publishView(page);
	await checkViewOnFrontEnd(page);

	page.on('dialog', (dialog) => console.log(dialog.message()));

	await page.getByRole('link', { name: 'Rain' }).click();
	await page.getByRole('link', { name: 'Edit Entry' }).click();

	const nameField = page.getByLabel('Name(Required)');
	await expect(nameField).toHaveValue('Rain');

	const initialFiles = page.locator('.ginput_preview');
	await expect(initialFiles).toHaveCount(1);

	await nameField.fill('');

	let uploadInProgress = true;
	page.on('dialog', async (dialog) => {
		const message = dialog.message();

		if (message === 'Please wait for the uploading to complete') {
			uploadInProgress = true;
			await dialog.dismiss();
		} else {
			await dialog.accept();
		}
	});

	const deleteButtons = page.locator('.ginput_preview_control.gform-icon--circle-delete');
	await expect(deleteButtons).toHaveCount(1);

	await deleteButtons.first().click({ delay: 100 });
	await expect(page.locator('.ginput_preview')).toHaveCount(0);

	const blizzardImagePath = getTestImagePath('blizzard.jpg');
	const fileInput = page.locator('input[type="file"]._admin');
	await fileInput.waitFor({ state: 'attached' });
	await fileInput.setInputFiles(blizzardImagePath);

	const updateButton = page.getByRole('button', { name: 'Update' });
	while (uploadInProgress) {
		uploadInProgress = false;
		await updateButton.click();
		await page.waitForTimeout(1000);
	}

	await expect(page.getByText('This field is required.')).toBeVisible();
	await expect(
		page.locator('.ginput_preview').filter({ hasText: 'rain.jpg' })
	).toBeVisible();

	await expect(page.getByText('For security reasons you must upload the file again')).toBeVisible();

	await nameField.fill('Rain');

	await fileInput.setInputFiles(blizzardImagePath);
	await deleteButtons.first().click();
	await updateButton.click();

	await expect(page.getByText('Entry Updated. Return to Entry')).toBeVisible();

	await page.getByRole('link', { name: 'Return to Entry' }).click();
	await expect(page.locator('img[src*="blizzard"]')).toBeVisible();
});
