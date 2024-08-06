// @ts-check
const { exec } = require("child_process");
const { defineConfig, devices } = require("@playwright/test");

require("dotenv").config({ path: `${process.env.INIT_CWD}/.env` });

let containerId;

async function startDockerContainer() {
	console.log("Attempting to start Docker container...");
	return new Promise((resolve, reject) => {
		exec(
			"docker run -d --rm --network host --ipc=host jacoblincool/playwright:chromium-server",
			(err, stdout) => {
				if (err) {
					console.error("Error starting Docker container:", err);
					return reject(err);
				}
				containerId = stdout.trim();
				console.log("Docker container started with ID:", containerId);
				resolve();
			},
		);
	});
}

async function stopDockerContainer() {
	console.log("Attempting to stop Docker container...");
	return new Promise((resolve, reject) => {
		exec(
			"docker stop $(docker ps -q --filter ancestor=jacoblincool/playwright:chromium-server)",
			(err, stdout, stderr) => {
				if (err) {
					console.error("Error stopping Docker container:", err);
					console.error("stderr output:", stderr);
					return reject(err);
				}
				console.log("Docker container stopped");
				console.log("stdout output:", stdout);
				resolve();
			},
		);
	});
}

// Export the setup and teardown functions
module.exports.globalSetup = async () => {
	console.log("Global setup starting...");
	await startDockerContainer();
	console.log("Global setup completed.");
};

module.exports.globalTeardown = async () => {
	console.log("Global teardown starting...");
	await stopDockerContainer();
	console.log("Global teardown completed.");
};

/**
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig({
	testDir: "..",
	outputDir: "../results",
	snapshotPathTemplate:
		"{testDir}/snapshots/{testFileDir}/{testName}-snapshots/{arg}{ext}",
	/* Run tests in files in parallel */
	fullyParallel: true,
	/* Fail the build on CI if you accidentally left test.only in the source code. */
	forbidOnly: !!process.env.CI,
	/* Retry on CI only */
	retries: process.env.CI ? 2 : 0,
	/* Opt out of parallel tests on CI. */
	workers: process.env.CI ? 1 : undefined,
	/* Reporter to use. See https://playwright.dev/docs/test-reporters */
	reporter: [["list", { printSteps: true }]],
	/* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
	use: {
		/* Base URL to use in actions like `await page.goto('/')`. */
		baseURL: `${process.env.WP_ENV_URL}:${process.env.WP_ENV_PORT}`,

		/* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
		trace: "on-first-retry",
		deviceScaleFactor: 1,
	},

	/* Configure projects for major browsers */
	projects: [
		{
			name: "chromium",
			use: {
				...devices["Desktop Chrome"],
				viewport: { width: 1280, height: 1024 },
				connectOptions: {
					wsEndpoint: "ws://localhost:53333/playwright",
				},
			},
		},
	],

	globalSetup: require.resolve("./playwright.global.setup"),

	globalTeardown: require.resolve("./playwright.global.teardown"),

	/* Run your local dev server before starting the tests */
	webServer: {
		command: "npm run wp-env start",
		url: `${process.env.WP_ENV_URL}:${process.env.WP_ENV_PORT}`,
		reuseExistingServer: true,
	},
});
