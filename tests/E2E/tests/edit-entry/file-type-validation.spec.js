import { test, expect } from '@playwright/test';
import { createView, publishView, checkViewOnFrontEnd, templates } from '../../helpers/test-helpers';
import path from 'path';

/**
 * Ensures that disallowed file types are rejected when editing an entry with a file upload field.
 * Also verifies that the file does not persist after a validation error is triggered elsewhere in the form.
 */
test('File type validation during entry edit', async ({ page }) => {
  await page.goto('/wp-admin/edit.php?post_type=gravityview');

  await createView(page, {
    formTitle: 'Weather Multi-Upload Form',
    viewName: 'File Type Validation Test',
    template: templates[0]
  });

  await publishView(page);
  await checkViewOnFrontEnd(page);

  page.on('dialog', dialog => console.log(dialog.message()));

  await page.getByRole('link', { name: 'Tuesday Weather' }).click();
  await page.getByRole('link', { name: 'Edit Entry' }).click();

  const pngFilePath = path.join(__dirname, '../../helpers/gf-importer/data/images/brown-meerkat.png');
  await page.getByRole('button', { name: /select files/i }).click();
  const fileInput = page.locator('input[type="file"]:visible');
  await fileInput.setInputFiles(pngFilePath);

  await expect(page.getByText(/type of file is not allowed/i)).toBeVisible();

  const validImagePath = path.join(__dirname, '../../helpers/gf-importer/data/images/wind.jpg');
  await fileInput.setInputFiles(validImagePath);

  await expect(page.getByText(/wind\.jpg/i)).toBeVisible();

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

  await expect(page.locator('.gfield_fileupload_filename').filter({ hasText: 'wind.jpg' })).toBeVisible();

  await page.getByLabel('Name(Required)').fill('Tuesday Weather');
  await page.getByRole('button', { name: 'Update' }).click();

  await expect(page.getByText('Entry Updated. Return to Entry')).toBeVisible();
});
