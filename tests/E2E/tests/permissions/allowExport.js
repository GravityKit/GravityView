import { test, expect } from "@playwright/test";
import {
	checkViewOnFrontEnd,
	clickDownloadButton,
	createView,
	getViewUrl,
	gotoAndEnsureLoggedIn,
	publishView,
	templates,
} from "../../helpers/test-helpers";

/**
 * Verifies the "Allow Export" setting enables entry downloads successfully.
 */
test("Verify Allow Export", async ({ page }, testInfo) => {
	let success = false;

	page.on("download", (download) => {
		success = true;
	});

	await test.step("Log in and navigate to the appropriate page", async () => {
		await gotoAndEnsureLoggedIn(page, testInfo);
	});

	await test.step("Create a new View with the Favorite Color form", async () => {
		await createView(page, {
			formTitle: "Favorite Color",
			viewName: "Verify Allow Export Test",
			template: templates[0],
		});
	});

	await test.step("Enable the Allow Export setting", async () => {
		await page
			.locator("#gravityview_settings div")
			.getByRole("link", { name: "Permissions" })
			.click();
		await page.getByLabel("Allow Export").setChecked(true);
		await publishView(page);
	});

	let viewUrl;
	await test.step("Get the View URL", async () => {
		viewUrl = await getViewUrl(page);
	});

	const downloadUrl = `${viewUrl}csv/`;

	await test.step("Check the View on the front end and attempt to download entries", async () => {
		await checkViewOnFrontEnd(page);
		await clickDownloadButton(page, downloadUrl);
	});

	await test.step("Verify the download was successful", async () => {
		expect(success).toBe(true);
	});
});
