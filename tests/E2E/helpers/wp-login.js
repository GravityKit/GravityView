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
		console.error("An error occurred during login:", error);

		console.log("Logging in and saving state…");

		try {
			const loginPage = "wp-login.php";
			console.log(`Navigating to: ${baseUrl}/${loginPage}`);

			await page.goto(`${baseUrl}/${loginPage}`);

			console.log("Filling in username and password fields");
			await page.fill("#user_login", username);
			await page.fill("#user_pass", password);

			console.log("Clicking submit button");
			await page.click("#wp-submit");

			console.log("Waiting for navigation to complete");
			await page.waitForNavigation({ waitUntil: "networkidle" });

			console.log(`Current URL after login: ${page.url()}`);
			if (page.url().includes(loginPage)) {
				throw new Error("WordPress login failed");
			}

			console.log(`Saving state to: ${stateFile}`);
			await page.context().storageState({ path: stateFile });
		} catch (innerError) {
			console.error(
				"An error occurred inside the catch block:",
				innerError,
			);
		}
	}
}

module.exports = { wpLogin };
