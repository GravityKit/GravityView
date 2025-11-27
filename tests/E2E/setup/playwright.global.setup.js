const { chromium } = require('playwright');
const { exec } = require('child_process');
const { wpLogin } = require('../helpers/wp-login');
const path = require('path');
const { loadEnv } = require('../helpers/misc');

async function startDockerContainer() {
	return new Promise((resolve, reject) => {
		exec(
			'docker run -d --rm --network host --ipc=host jacoblincool/playwright:chromium-server-1.56.1',
			(error, stdout) => {
				if (error) {
					console.error('Error starting Docker container:', error);

					return reject(error);
				}

				console.log('Docker container started with ID:', stdout.trim().substring(0, 12));

				resolve();
			}
		);
	});
}

async function waitForWsEndpoint(wsEndpoint, retries = 5, delay = 2000) {
	for (let i = 0; i < retries; i++) {
		try {
			return await chromium.connect({ wsEndpoint });
		} catch {
			console.log(`Retrying connection to wsEndpoint: ${wsEndpoint} (${i + 1}/${retries})`);

			await new Promise((resolve) => setTimeout(resolve, delay));
		}
	}
	throw new Error(
		`Failed to connect to Playwright WebSocket: ${wsEndpoint} after ${retries} retries`
	);
}

module.exports = async (config) => {
	loadEnv();

	const projectConfig = config.projects.find((project) => project.name === 'chromium');

	let browser;

	if (process.env.USE_LOCAL_BROWSER === '1') {
		console.log('Using Docker Playwright browser.');

		try {
			browser = await chromium.launch();
		} catch (error) {
			console.error(`Failed to launch Playwright browser: ${error.message}`);

			process.exit(1);
		}
	} else {
		console.log('Using local Playwright browser.');

		const {
			connectOptions: { wsEndpoint }
		} = projectConfig.use;

		try {
			await startDockerContainer();
		} catch (error) {
			console.error(`Failed to start Docker container: ${error.message}`);

			process.exit(1);
		}

		try {
			browser = await waitForWsEndpoint(wsEndpoint);
		} catch (error) {
			console.error(error.message);

			process.exit(1);
		}
	}

	try {
		const { storageState, baseURL } = projectConfig.use;

		await wpLogin({
			browser,
			storageState,
			baseURL,
			username: process.env.WP_ENV_USER,
			password: process.env.WP_ENV_USER_PASS
		});
	} catch (error) {
		console.error(error.message);

		process.exit(1);
	}

	await browser.close();
};
