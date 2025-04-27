const fs = require('fs').promises;
const { sleep } = require('./misc');

async function waitForWordPress(page, tries = 40, delay = 3000) {
	// Poll wp-login.php until it responds with HTTP 200.
	for (let i = 0; i < tries; i++) {
		try {
			const res = await page.goto('/wp-login.php', {
				waitUntil: 'domcontentloaded',
				timeout: 15000
			});
			if (res && res.status() === 200) return;
		} catch {
			/* ignore timeouts, keep polling */
		}
		await sleep(delay);
	}

	throw new Error('WordPress did not become ready in time.');
}

async function attemptLogin(page, { username, password }) {
	await page.goto('/wp-login.php');

	await page.fill('#user_login', username);

	await page.fill('#user_pass', password);

	await Promise.all([
		page.waitForURL('/wp-admin/**', {
			waitUntil: 'domcontentloaded',
			timeout: 60000
		}),
		page.click('#wp-submit')
	]);
}

async function wpLogin({ browser, storageState, baseURL, username, password }) {
	let storageStateExists = false;

	try {
		await fs.access(storageState);

		storageStateExists = true;
	} catch {
		console.log('No authentication state file found. Logging in…');
	}

	const context = storageStateExists
		? await browser.newContext({ storageState, baseURL })
		: await browser.newContext({ baseURL });
	const page = await context.newPage();

	// Validate existing session
	if (storageStateExists) {
		console.log('Checking authentication state…');

		await page.goto('/wp-admin', {
			waitUntil: 'domcontentloaded',
			timeout: 60000
		});

		if (await page.$('#wpadminbar')) {
			console.log('Valid session detected, continuing…');

			await context.close();

			return;
		}

		console.log('Stale authentication state detected. Logging in…');
	}

	await waitForWordPress(page);

	for (let attempt = 1; attempt <= 3; attempt++) {
		try {
			await attemptLogin(page, { username, password });

			await page.context().storageState({ path: storageState });

			console.log('New authentication state saved.');

			await context.close();

			return;
		} catch {
			if (attempt === 3) {
				throw new Error('WordPress login failed after 3 attempts.');
			}

			console.log(`Login attempt ${attempt} failed, retrying…`);

			await sleep(3000);
		}
	}
}

module.exports = { wpLogin };
