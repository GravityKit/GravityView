// @ts-check
import { test, expect } from "@wordpress/e2e-test-utils-playwright";

require("dotenv").config({ path: `${process.env.INIT_CWD}/.env` });

test("Admin page loads", async ({ admin, page, headless }) => {
	await page.goto("/wp-admin");

	await page.fill("#user_login", process.env.WP_ENV_USER);
	await page.fill("#user_pass", process.env.WP_ENV_USER_PASS);

	await page.click("#wp-submit");

	await admin.visitAdminPage("/");

	await expect(
		page.getByRole("heading", { name: "Welcome to WordPress", level: 2 }),
	).toBeVisible();
});
