import { test, expect } from "@playwright/test";
import {
	checkViewOnFrontEnd,
	createView,
	gotoAndEnsureLoggedIn,
	publishView,
	templates,
} from "../../helpers/test-helpers";

/**
 * Tests that a custom slug is correctly applied to single entry URLs.
 */
test("Verify Single Entry Custom Slug", async ({ page }, testInfo) => {
	await gotoAndEnsureLoggedIn(page, testInfo);
	await createView(page, {
		formTitle: "Favorite Book",
		viewName: "Verify Single Entry Custom Slug Test",
		template: templates[0],
	});
	await page
		.locator("#gravityview_settings div")
		.getByRole("link", { name: "Single Entry" })
		.click();
	const customSlug = "custom-{entry_id}";
	await page.getByLabel("Entry Slug").fill(customSlug);
	await publishView(page);
	await checkViewOnFrontEnd(page);
	await page.getByRole("link", { name: "Alice" }).click();
	const currentURL = page.url();
	const urlPattern = /entry\/custom-\d+\/$/;
	expect(currentURL).toMatch(urlPattern);
});
