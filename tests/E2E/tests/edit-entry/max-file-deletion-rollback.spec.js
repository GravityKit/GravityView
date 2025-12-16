import { test, expect } from '@playwright/test';
import { createView, publishView, checkViewOnFrontEnd, templates, getTestImagePath } from '../../helpers/test-helpers';

/**
 * Ensures deleted files are not restored when validation fails after editing an entry with a file upload limit.
 */
test('Does not restore deleted files after validation failure', async ({ page }, testInfo) => {
  await page.goto('/wp-admin/edit.php?post_type=gravityview');

  await createView(page, {
    formTitle: 'Weather Multi-Upload Form',
    viewName: 'File Deletion Rollback Test',
    template: templates[0]
  }, testInfo);

  await publishView(page);
  await checkViewOnFrontEnd(page);

  await page.getByRole('link', { name: 'Monday Weather' }).click();
  await page.getByRole('link', { name: 'Edit Entry' }).click();

  const initialFiles = page.locator('.ginput_preview');
  await expect(initialFiles).toHaveCount(1);
  await expect(page.getByLabel('Name(Required)')).toHaveValue('Monday Weather');

  await page.getByLabel('Name(Required)').fill('');

  let uploadInProgress = true;
  page.on('dialog', async dialog => {
    const message = dialog.message();
  
    if (message === 'Please wait for the uploading to complete') {
      uploadInProgress = true;
      await dialog.dismiss();
    } else {
      await dialog.accept(); // fallback: accept all other dialogs
    }
  });  

  const deleteButtons = page.locator('.ginput_preview_control.gform-icon--circle-delete');
  await expect(deleteButtons).toHaveCount(1);
  
  await deleteButtons.first().click();
  await expect(page.locator('.ginput_preview')).toHaveCount(0);

  const fogImagePath = getTestImagePath('fog.jpg');
  const blizzardImagePath = getTestImagePath('blizzard.jpg');

  await page.getByRole('button', { name: /select files/i }).click();
  const fileInput = page.locator('input[type="file"]:visible');
  await fileInput.setInputFiles([fogImagePath, blizzardImagePath]);

  await expect(page.getByText('fog.jpg', { exact: true })).toBeVisible();
  await expect(page.getByText('blizzard.jpg', { exact: true })).toBeVisible();

  const updateButton = page.getByRole('button', { name: 'Update' });
  while (uploadInProgress) {
    uploadInProgress = false;
    await updateButton.click();
    await page.waitForTimeout(1000);
  }

  await expect(page.getByText('This field is required.')).toBeVisible();

  const remainingFiles = page.locator('.ginput_preview');
  await expect(remainingFiles).toHaveCount(2);
  await expect(page.locator('.gfield_fileupload_filename').filter({ hasText: 'fog.jpg' })).toBeVisible();
  await expect(page.locator('.gfield_fileupload_filename').filter({ hasText: 'blizzard.jpg' })).toBeVisible();
    

  await page.getByLabel('Name(Required)').fill('Monday Weather');
  await page.getByRole('button', { name: 'Update' }).click();

  await expect(page.getByText('Entry Updated. Return to Entry')).toBeVisible();
});