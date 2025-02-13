import { test, expect } from "@playwright/test";
import {
	createPageWithShortcode,
	createView,
	gotoAndEnsureLoggedIn,
	publishView,
	templates,
} from "../../helpers/test-helpers";

/**
 * Ensures a View without a valid secret shows an error message.
 */
test("Verify Enhanced Security", async ({ page }, testInfo) => {
	await test.step("Log in and navigate to the appropriate page", async () => {
		await gotoAndEnsureLoggedIn(page, testInfo);
	});

	await test.step("Create a View with the Favorite Color form", async () => {
		await createView(page, {
			formTitle: "Favorite Color",
			viewName: "Verify Enhanced Security Test",
			template: templates[0],
		});
	});

	await test.step("Publish the View", async () => {
		await publishView(page);
	});

	const currentUrl = page.url();
	const params = new URLSearchParams(new URL(currentUrl).search);
	const viewId = params.get("post");

	await test.step("Create a page with a shortcode for the View", async () => {
		const url = await createPageWithShortcode(page, {
			shortcode: `gravityview id="${viewId}"`,
			title: "Verify Enhanced Security Test Page",
		});
		await page.goto(url);
	});

	await test.step("Check for the error message when no valid secret is provided", async () => {
		const errorMessage = page.locator("text=Invalid View secret provided");
		await expect(errorMessage).toBeVisible();
	});
});
