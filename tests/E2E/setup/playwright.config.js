const path = require('path');
const { defineConfig, devices } = require('@playwright/test');

require('dotenv').config({ path: `${process.env.INIT_CWD}/.env` });

module.exports = defineConfig({
  testDir: path.resolve(__dirname, '..'),
  outputDir: path.resolve(__dirname, '../results'),
  snapshotPathTemplate:
    '{testDir}/.playwright/snapshots/{testFileDir}/{testName}-snapshots/{arg}{ext}',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [
    [
      'html',
      { open: 'never', outputFolder: path.resolve(__dirname, '../report') },
    ],
  ],
  use: {
    baseURL: `${process.env.WP_ENV_URL}:${process.env.WP_ENV_PORT}`,
    trace: 'on-first-retry',
    deviceScaleFactor: 1,
    viewport: { width: 1280, height: 1024 },
  },
  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        viewport: { width: 1280, height: 1024 },
        connectOptions: {
          wsEndpoint: 'ws://localhost:53333/playwright',
        },
        storageState: path.resolve(__dirname, '.state.json'),
      },
    },
  ],
  globalSetup: path.resolve(__dirname, 'playwright.global.setup.js'),
  globalTeardown: path.resolve(__dirname, 'playwright.global.teardown.js'),
  webServer: {
    command: 'npm run wp-env start',
    url: `${process.env.WP_ENV_URL}:${process.env.WP_ENV_PORT}`,
    reuseExistingServer: true,
  },
});
