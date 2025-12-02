import { test, expect } from '@playwright/test';
import { createView, publishView, checkViewOnFrontEnd, templates, getTestImagePath } from '../../helpers/test-helpers';

/**
 * Ensures that files exceeding the maximum allowed upload size are rejected when editing an entry.
 * Also verifies that the file does not persist after a validation error is triggered elsewhere in the form.
 */
test('File size limit validation during entry edit', async ({ page }) => {
  await page.goto('/wp-admin/edit.php?post_type=gravityview');

  await createView(page, {
    formTitle: 'Weather Multi-Upload Form',
    viewName: 'File Size Limit Test',
    template: templates[0]
  });

  await publishView(page);
  await checkViewOnFrontEnd(page);

  await page.getByRole('link', { name: 'Thursday Weather' }).click();
  await page.getByRole('link', { name: 'Edit Entry' }).click();

  const largeFilePath = getTestImagePath('thunderstorm.jpg');
  await page.getByRole('button', { name: /select files/i }).click();
  const fileInput = page.locator('input[type="file"]:visible');
  await fileInput.setInputFiles(largeFilePath);

  await expect(page.getByText(/File exceeds size limit/i)).toBeVisible();

  const validImagePath = getTestImagePath('fog.jpg');
  await fileInput.setInputFiles(validImagePath);

  await expect(page.getByText('fog.jpg', { exact: true })).toBeVisible();

  await page.getByLabel('Name(Required)').fill('');

  let uploadInProgress = true;
  page.on('dialog', async dialog => {
    if (dialog.message() === 'Please wait for the uploading to complete') {
      uploadInProgress = true;
      await dialog.dismiss();
    }
  });

  const updateButton = page.getByRole('button', { name: 'Update' });
  while (uploadInProgress) {
    uploadInProgress = false;
    await updateButton.click();
    await page.waitForTimeout(1000);
  }

  await expect(page.getByText('This field is required.')).toBeVisible();

  await expect(page.locator('.gfield_fileupload_filename').filter({ hasText: 'fog.jpg' })).toBeVisible();

  await page.getByLabel('Name(Required)').fill('Thursday Weather');
  await page.getByRole('button', { name: 'Update' }).click();

  await expect(page.getByText('Entry Updated. Return to Entry')).toBeVisible();
});
