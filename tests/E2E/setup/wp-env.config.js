// This creates a .wp-env.json file using the .env file in the root of the project.

const fs = require('fs');
const path = require('path');
const { loadEnv } = require('../helpers/misc');

loadEnv();

const wpEnvConfig = {
	phpVersion: process.env.WP_ENV_PHP_VERSION || '7.4',
	plugins: [
		'../../..', // GravityView
		'../helpers/gf-importer', // GF Importer - relative to setup directory
		...(process.env.WP_ENV_PLUGINS
			? process.env.WP_ENV_PLUGINS.split(',').map((plugin) => plugin.trim())
			: [])
	],
	port: parseInt(process.env.WP_ENV_PORT, 10) || 8888,
	config: {
		WP_DEBUG: true,
		WP_DEBUG_LOG: true,
		WP_DEBUG_DISPLAY: true,
		SAVEQUERIES: true,
		SCRIPT_DEBUG: true
	},
	mappings: {
		'wp-cli.yml': './wp-cli.wp-env.yml'
	},
	lifecycleScripts: {
		afterStart: [
			"npm run wp-env:cli wp rewrite structure '/%postname%/' -- --hard",
			`npm run wp-env:cli wp eval-file wp-content/plugins/${path.basename(process.env.INIT_CWD)}/tests/E2E/helpers/gf-importer/gf-importer.php`,
			'npm run wp-env:cli wp plugin install gravityformscli pexlechris-adminer -- --activate',
			'npm run wp-env:cli wp option update gform_pending_installation 0', // Prevents the setup wizard from running.
			`npm run wp-env:cli wp gf license update ${process.env.GRAVITY_FORMS_LICENSE_KEY}`,
			`npm run wp-env:cli wp gk licenses activate ${process.env.GRAVITYKIT_LICENSE_KEY} -- --url=${process.env.WP_ENV_URL} || true`
		].join(' && ')
	}
};

fs.writeFileSync(
	path.join(__dirname, '.wp-env.json'),

	JSON.stringify(wpEnvConfig, null, 2)
);

console.log('Generated .wp-env.json');
