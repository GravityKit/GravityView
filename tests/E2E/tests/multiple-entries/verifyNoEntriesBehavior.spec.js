import { test, expect } from '@playwright/test';
import {
	checkViewOnFrontEnd,
	createView,
	publishView,
	templates
} from '../../helpers/test-helpers';

/**
 * Verifies behavior when no entries are present.
 */
test('Verify No Entries Behavior', async ({ page }, testInfo) => {
	await page.goto('/wp-admin/edit.php?post_type=gravityview');
	await createView(
		page,
		{
			formTitle: 'No Entries',
			viewName: 'Verify No Entries Behavior Test',
			template: templates[0]
		},
		testInfo
	);
	await publishView(page);
	await checkViewOnFrontEnd(page);
	await expect(page.getByText('No entries match your request.')).toBeVisible();
});
