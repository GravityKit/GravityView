// @ts-check
import { test, expect } from "@wordpress/e2e-test-utils-playwright";
const path = require("path");

require("dotenv").config({ path: `${process.env.INIT_CWD}/.env` });

test("GravityKit menu is available", async ({ admin, page, headless }) => {
	await page.goto("/wp-admin");

	await page.fill("#user_login", process.env.WP_ENV_USER);
	await page.fill("#user_pass", process.env.WP_ENV_USER_PASS);

	await page.click("#wp-submit");

	await page.waitForSelector("#toplevel_page__gk_admin_menu");
	await page.hover("#toplevel_page__gk_admin_menu");

	await page.waitForSelector("#toplevel_page__gk_admin_menu .wp-submenu");

	const submenus = await page
		.locator("#toplevel_page__gk_admin_menu .wp-submenu a")
		.allTextContents();

	const submenuTitles = submenus.map((item) =>
		item.replace(/\d+$/, "").trim(),
	); // Remove the trailing number as it's a hidden <span class="plugin-count">0</span> element.

	expect(submenuTitles).toContain("Manage Your Kit");
	expect(submenuTitles).toContain("Settings");
	expect(submenuTitles).toContain("Grant Support Access");
});
