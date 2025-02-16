import { test, expect } from "@playwright/test";
import {
	createView,
	gotoAndEnsureLoggedIn,
	publishView,
	templates,
} from "../../helpers/test-helpers";

/**
 * Verifies that REST API access is enabled when 'Allow REST Access' is checked.
 */
test("Verify Allow Rest Access Checked", async ({ page }, testInfo) => {
	let currentUrl, params, viewId, baseUrl, apiUrl, response;

	await test.step("Log in and navigate to the appropriate page", async () => {
		await gotoAndEnsureLoggedIn(page, testInfo);
	});

	await test.step("Create a new View with the Favorite Color form", async () => {
		await createView(page, {
			formTitle: "Favorite Color",
			viewName: "Verify Allow Rest Access Checked Test",
			template: templates[0],
		});
	});

	await test.step("Enable the Allow REST Access setting", async () => {
		await page
			.locator("#gravityview_settings div")
			.getByRole("link", { name: "Permissions" })
			.click();
		await page.getByLabel("Allow REST Access").setChecked(true);
		await publishView(page);
	});

	await test.step("Generate the API URL for the View", async () => {
		currentUrl = page.url();
		params = new URLSearchParams(new URL(currentUrl).search);
		viewId = params.get("post");
		baseUrl = new URL(currentUrl).origin;
		apiUrl = `${baseUrl}/wp-json/gravityview/v1/views/${viewId}`;
	});

	let responsePromise;
	await test.step("Wait for the REST API response and visit the API URL", async () => {
		responsePromise = page.waitForResponse(apiUrl);
		await page.goto(apiUrl);
	});

	await test.step("Verify that the REST API request is successful", async () => {
		response = await responsePromise;
		expect(response.ok()).toBe(true);
	});
});
