import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { createView, templates } from '../../helpers/test-helpers';

/**
 * Tests AJAX save functionality with keyboard shortcuts and toast notifications.
 */
test.describe.serial('GravityView AJAX Save', () => {
	let testViewCreated = false;
	let viewUrl = '';

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage();

		// Create a test view to work with
		await page.goto('/wp-admin/edit.php?post_type=gravityview');
		await createView(
			page,
			{
				formTitle: 'A Simple Form',
				viewName: 'AJAX Save Test View',
				template: templates[0]
			}
		);

		// Get the view URL for later tests
		await page.click('#publish');
		await page.waitForURL(/\/wp-admin\/post\.php\?post=\d+&action=edit/);
		viewUrl = page.url();
		testViewCreated = true;

		await page.close();
	});

	test('Save view with Command+S (Mac) keyboard shortcut', async ({ page }) => {
		test.skip(!testViewCreated, 'Test view was not created successfully');

		// Navigate to the view edit page
		await page.goto(viewUrl);
		await page.waitForSelector('#gravityview_settings', { state: 'visible' });

		// Make a change to trigger unsaved state
		const viewTitle = await page.locator('#title');
		await viewTitle.fill('AJAX Save Test View - Modified');

		// Wait for the page to be fully loaded
		await page.waitForLoadState('networkidle');

		// Listen for network requests to verify AJAX call
		const ajaxPromise = page.waitForResponse(
			(response) =>
				response.url().includes('admin-ajax.php') &&
				response.request().postData()?.includes('action=gravityview_save_view')
		);

		// Press Command+S (or Ctrl+S on Windows/Linux)
		const isMac = process.platform === 'darwin';
		if (isMac) {
			await page.keyboard.press('Meta+s');
		} else {
			await page.keyboard.press('Control+s');
		}

		// Wait for AJAX request to complete
		const ajaxResponse = await ajaxPromise;
		expect(ajaxResponse.status()).toBe(200);

		// Verify success toast appears
		const successToast = page.locator('.gv-toast-success');
		await expect(successToast).toBeVisible({ timeout: 5000 });
		await expect(successToast).toContainText(/View saved successfully/i);

		// Verify toast has proper structure
		await expect(successToast.locator('.gv-toast-icon')).toBeVisible();
		await expect(successToast.locator('.gv-toast-message')).toBeVisible();
		await expect(successToast.locator('.gv-toast-progress')).toBeVisible();

		// Wait for toast to auto-hide
		await expect(successToast).not.toBeVisible({ timeout: 10000 });
	});

	test('Save view with Ctrl+S (Windows/Linux) keyboard shortcut', async ({ page }) => {
		test.skip(!testViewCreated, 'Test view was not created successfully');

		// Navigate to the view edit page
		await page.goto(viewUrl);
		await page.waitForSelector('#gravityview_settings', { state: 'visible' });

		// Make a change
		const viewTitle = await page.locator('#title');
		await viewTitle.fill('AJAX Save Test View - Modified Again');

		await page.waitForLoadState('networkidle');

		// Listen for AJAX request
		const ajaxPromise = page.waitForResponse(
			(response) =>
				response.url().includes('admin-ajax.php') &&
				response.request().postData()?.includes('action=gravityview_save_view')
		);

		// Press Ctrl+S
		await page.keyboard.press('Control+s');

		// Wait for AJAX request
		const ajaxResponse = await ajaxPromise;
		expect(ajaxResponse.status()).toBe(200);

		// Verify success toast
		const successToast = page.locator('.gv-toast-success');
		await expect(successToast).toBeVisible({ timeout: 5000 });
	});

	test('Verify toast respects prefers-reduced-motion', async ({ page, context }) => {
		test.skip(!testViewCreated, 'Test view was not created successfully');

		// Emulate reduced motion preference
		await context.addInitScript(() => {
			Object.defineProperty(window, 'matchMedia', {
				writable: true,
				value: (query) => {
					if (query === '(prefers-reduced-motion: reduce)') {
						return {
							matches: true,
							media: query,
							onchange: null,
							addListener: () => {},
							removeListener: () => {},
							addEventListener: () => {},
							removeEventListener: () => {},
							dispatchEvent: () => {}
						};
					}
					return window.matchMedia(query);
				}
			});
		});

		// Navigate to the view edit page
		await page.goto(viewUrl);
		await page.waitForSelector('#gravityview_settings', { state: 'visible' });

		// Make a change
		const viewTitle = await page.locator('#title');
		await viewTitle.fill('AJAX Save Test View - Reduced Motion');

		// Trigger save
		await page.keyboard.press('Control+s');

		// Wait for toast to appear
		const successToast = page.locator('.gv-toast-success');
		await expect(successToast).toBeVisible({ timeout: 5000 });

		// Verify progress bar is hidden for reduced motion
		const progressBar = successToast.locator('.gv-toast-progress');
		await expect(progressBar).not.toBeVisible();

		// Verify toast still has full opacity (no animation)
		const opacity = await successToast.evaluate((el) =>
			window.getComputedStyle(el).opacity
		);
		expect(parseFloat(opacity)).toBe(1);
	});

	test('Verify AJAX save persists changes', async ({ page }) => {
		test.skip(!testViewCreated, 'Test view was not created successfully');

		// Navigate to the view edit page
		await page.goto(viewUrl);
		await page.waitForSelector('#gravityview_settings', { state: 'visible' });

		// Make a unique change we can verify
		const uniqueTitle = `AJAX Save Test - ${Date.now()}`;
		const viewTitle = await page.locator('#title');
		await viewTitle.fill(uniqueTitle);

		// Save via AJAX
		await page.keyboard.press('Control+s');

		// Wait for success toast
		const successToast = page.locator('.gv-toast-success');
		await expect(successToast).toBeVisible({ timeout: 5000 });
		await expect(successToast).not.toBeVisible({ timeout: 10000 });

		// Reload the page
		await page.reload();
		await page.waitForSelector('#gravityview_settings', { state: 'visible' });

		// Verify the change persisted
		const reloadedTitle = await page.locator('#title').inputValue();
		expect(reloadedTitle).toBe(uniqueTitle);
	});

	test('Publish button still works as fallback', async ({ page }) => {
		test.skip(!testViewCreated, 'Test view was not created successfully');

		// Navigate to the view edit page
		await page.goto(viewUrl);
		await page.waitForSelector('#gravityview_settings', { state: 'visible' });

		// Make a change
		const viewTitle = await page.locator('#title');
		await viewTitle.fill('AJAX Save Test View - Publish Button');

		await page.waitForLoadState('networkidle');

		// Listen for AJAX request
		const ajaxPromise = page.waitForResponse(
			(response) =>
				response.url().includes('admin-ajax.php') &&
				response.request().postData()?.includes('action=gravityview_save_view'),
			{ timeout: 3000 }
		).catch(() => null);

		// Click publish button
		await page.click('#publish');

		// Check if AJAX was attempted
		const ajaxResponse = await ajaxPromise;

		if (ajaxResponse) {
			// AJAX save succeeded - verify toast appears
			const successToast = page.locator('.gv-toast-success');
			await expect(successToast).toBeVisible({ timeout: 5000 });
		} else {
			// AJAX failed or wasn't attempted - verify traditional save worked
			await page.waitForSelector('.notice-success', { timeout: 5000 });
			const successNotice = await page.textContent('.notice-success');
			expect(successNotice).toMatch(/View (published|updated)/);
		}
	});

	test('Error toast displays on AJAX failure', async ({ page }) => {
		test.skip(!testViewCreated, 'Test view was not created successfully');

		// Navigate to the view edit page
		await page.goto(viewUrl);
		await page.waitForSelector('#gravityview_settings', { state: 'visible' });

		// Intercept AJAX request to simulate failure
		await page.route('**/admin-ajax.php*', (route) => {
			const postData = route.request().postData();
			if (postData && postData.includes('action=gravityview_save_view')) {
				route.fulfill({
					status: 500,
					contentType: 'application/json',
					body: JSON.stringify({
						success: false,
						data: {
							message: 'Test error: Simulated AJAX failure'
						}
					})
				});
			} else {
				route.continue();
			}
		});

		// Make a change
		const viewTitle = await page.locator('#title');
		await viewTitle.fill('AJAX Save Test View - Error Test');

		// Trigger save
		await page.keyboard.press('Control+s');

		// Verify error toast appears
		const errorToast = page.locator('.gv-toast-error');
		await expect(errorToast).toBeVisible({ timeout: 5000 });
		await expect(errorToast).toContainText(/error/i);
	});

	test('Multiple rapid saves are handled correctly', async ({ page }) => {
		test.skip(!testViewCreated, 'Test view was not created successfully');

		// Navigate to the view edit page
		await page.goto(viewUrl);
		await page.waitForSelector('#gravityview_settings', { state: 'visible' });

		const viewTitle = await page.locator('#title');

		// Make multiple rapid changes and save attempts
		for (let i = 0; i < 3; i++) {
			await viewTitle.fill(`AJAX Save Test - Rapid ${i}`);
			await page.keyboard.press('Control+s');
			// Short delay between saves
			await page.waitForTimeout(100);
		}

		// Immediately count visible success toasts (should handle rapid saves gracefully)
		const visibleToasts = await page.locator('.gv-toast-success:visible').count();

		// Either all saves completed (3 toasts) or some were prevented by isSavingAjax flag
		expect(visibleToasts).toBeGreaterThanOrEqual(1);
		expect(visibleToasts).toBeLessThanOrEqual(3);
	});
});
