import { test, expect } from '@playwright/test';
import {
	createView,
	publishView,
	checkViewOnFrontEnd,
	templates
} from '../../helpers/test-helpers';

/**
 * Ensures that files remain unchanged when canceling an edit operation that includes file deletion.
 */
test('File persistence when canceling file deletion', async ({ page }) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');

	await createView(page, {
		formTitle: 'Weather Multi-Upload Form',
		viewName: 'File Persistence Test',
		template: templates[0]
	});

	await publishView(page);
	await checkViewOnFrontEnd(page);

	await page.getByRole('link', { name: 'Friday Weather' }).click();
	const viewUrl = page.url();
    await page.getByRole('link', { name: 'Edit Entry' }).click();
    
    page.on('dialog', dialog => dialog.accept());

	await page
		.locator('.ginput_preview_control.gform-icon--circle-delete')
		.first()
		.click();

	await expect(page.locator('.ginput_preview')).not.toBeVisible();
	await page.getByRole('link', { name: 'Cancel' }).click();
	await page.goto(viewUrl);
    await expect(page.locator('img[src*="snow"]')).toBeVisible();
});
