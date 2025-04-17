const fs = require('fs').promises;

async function wpLogin({ browser, storageState, baseURL, username, password }) {
	let storageStateExists = false;

	try {
		await fs.access(storageState);

		storageStateExists = true;
	} catch {
		console.log('No authentication state file found. Logging in…');
	}

	// **Create context with or without `storageState`**
	const context = storageStateExists
		? await browser.newContext({ storageState, baseURL })
		: await browser.newContext({ baseURL });

	const page = await context.newPage();

	// If state file exists, verify if the session is valid
	if (storageStateExists) {
		console.log('Checking authentication state…');

		await page.goto('/wp-admin', { waitUntil: 'domcontentloaded', timeout: 60000 });

		if (await page.$('#wpadminbar')) {
			console.log('Valid session detected, continuing…');

			await context.close();

			return;
		}

		console.log('Stale authentication state detected. Logging in…');
	}

	await page.goto('/wp-login.php');
	await page.fill('#user_login', username);
	await page.fill('#user_pass', password);
	await page.click('#wp-submit');

	try {
		await page.waitForURL('/wp-admin/**', { waitUntil: 'domcontentloaded', timeout: 60000 });
	} catch {
		throw new Error('WordPress login failed. Please check credentials.');
	}

	await page.context().storageState({ path: storageState });

	console.log('New authentication state saved.');

	await context.close();
}

module.exports = { wpLogin };
