import { test, expect } from '@playwright/test';
import { createView, publishView, checkViewOnFrontEnd, templates, getTestImagePath } from '../../helpers/test-helpers';

/**
 * Ensures that uploaded files are not lost when a validation error occurs during entry editing.
 */
test('File persistence during entry edit validation', async ({ page }, testInfo) => {
  await page.goto('/wp-admin/edit.php?post_type=gravityview');

  await createView(page, {
    formTitle: 'Weather Multi-Upload Form',
    viewName: 'File Persistence Test',
    template: templates[0]
  }, testInfo);

  await publishView(page);
  await checkViewOnFrontEnd(page);

  page.on('dialog', dialog => console.log(dialog.message()));

  await page.getByRole('link', { name: 'Wednesday Weather' }).click();
  await page.getByRole('link', { name: 'Edit Entry' }).click();

  await page.getByLabel('Name(Required)').fill('');

  const windImagePath = getTestImagePath('wind.jpg');
  await page.getByRole('button', { name: /select files/i }).click();
  const fileInput = page.locator('input[type="file"]:visible');
  await fileInput.setInputFiles(windImagePath);

  await expect(page.getByText(/wind\.jpg/i)).toBeVisible();

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

  await expect(page.locator('.gfield_fileupload_filename').filter({ hasText: 'wind.jpg' })).toBeVisible();

  await page.getByLabel('Name(Required)').fill('Wednesday Weather Updated');
  await page.getByRole('button', { name: 'Update' }).click();

  await expect(page.getByText('Entry Updated. Return to Entry')).toBeVisible();
});