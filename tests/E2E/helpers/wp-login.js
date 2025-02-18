const path = require("path");
const fs = require("fs").promises;

require("dotenv").config({ path: `${process.env.INIT_CWD}/.env` });

async function wpLogin({
	page,
	stateFile,
	username = process.env.WP_ENV_USER,
	password = process.env.WP_ENV_USER_PASS,
} = {}) {
	const baseUrl =
		`${process.env.WP_ENV_URL}:${process.env.WP_ENV_PORT}` ||
		"http://localhost";

	try {
		await fs.access(stateFile);

		const { cookies } = JSON.parse(await fs.readFile(stateFile, "utf-8"));

		console.log("Loading previously saved state…");

		await page.context().addCookies(cookies);
	} catch (error) {
		console.log("Logging in and saving state…");

		await page.goto(`${baseUrl}/wp-login.php`);

		await page.fill("#user_login", username);
		await page.fill("#user_pass", password);

		await page.click("#wp-submit");

		if (!(await page.waitForURL(`${baseUrl}/wp-admin/**`), { waitUntil: 'domcontentloaded', timeout: 6000 })) {
			throw new Error("WordPress login failed");
		};

		await page.context().storageState({ path: stateFile });
	}
}

module.exports = { wpLogin };
