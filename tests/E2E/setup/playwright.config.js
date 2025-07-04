const path = require('path');
const { defineConfig, devices } = require('@playwright/test');
const { loadEnv } = require('../helpers/misc');

loadEnv();

const useLocalBrowser = process.env.USE_LOCAL_BROWSER === '1';

module.exports = defineConfig({
	testDir: process.env.TEST_DIR || path.resolve(__dirname, '..'),
	outputDir: path.resolve(__dirname, '../results'),
	snapshotPathTemplate: '{testDir}/snapshots/{testFileDir}/{testName}-snapshots/{arg}{ext}',
	fullyParallel: true,
	forbidOnly: !!process.env.CI,
	retries: 0,
	workers: '50%',
	reporter: [
		[
			'html',
			{
				open: 'never',
				outputFolder: path.resolve(__dirname, '../report')
			}
		],
		['junit', { outputFile: path.resolve(__dirname, '../results/junit.xml') }]
	],
	use: {
		screenshot: 'only-on-failure',
		trace: 'retain-on-failure',
		video: 'retain-on-failure',
		baseURL: `${process.env.WP_ENV_URL}:${process.env.WP_ENV_PORT}`,
		trace: 'on-first-retry',
		deviceScaleFactor: 1,
		viewport: { width: 1280, height: 1024 }
	},
	projects: [
		{
			name: 'chromium',
			use: {
				...devices['Desktop Chrome'],
				viewport: { width: 1280, height: 1024 },
				...(useLocalBrowser
					? {}
					: {
							connectOptions: {
								wsEndpoint: 'ws://localhost:53333/playwright'
							}
						}),
				storageState: path.resolve(__dirname, '.state.json')
			}
		}
	],
	globalSetup: path.resolve(__dirname, 'playwright.global.setup.js'),
	globalTeardown: path.resolve(__dirname, 'playwright.global.teardown.js'),
	webServer: {
		command: 'npm run wp-env start',
		url: `${process.env.WP_ENV_URL}:${process.env.WP_ENV_PORT}`,
		reuseExistingServer: true
	}
});
