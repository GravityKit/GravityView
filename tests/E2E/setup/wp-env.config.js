// This creates a .wp-env.json file using the .env file in the root of the project.

const fs = require("fs");
const path = require("path");

require("dotenv").config({ path: `${process.env.INIT_CWD}/.env` });

const runAfterClean = [
	"rm -f .state.json ../helpers/gf-importer/.imported-forms.json",
];

const runAfterStart = [
	"npm run wp-env:cli wp import_forms_and_entries",
	"npm run wp-env:cli wp rewrite structure '/%postname%/' -- --hard",
	"npm run wp-env:cli wp plugin install gravityformscli --force --activate",
];

const wpEnvConfig = {
	phpVersion: process.env.WP_ENV_PHP_VERSION || "7.4",
	plugins: [
		"../../..", // GravityView
		...(process.env.WP_ENV_PLUGINS
			? process.env.WP_ENV_PLUGINS.split(",").map((plugin) =>
					plugin.trim(),
			  )
			: []),
	],
	port: parseInt(process.env.WP_ENV_PORT, 10) || 8888,
	config: {
		WP_DEBUG: true,
		WP_DEBUG_LOG: true,
		WP_DEBUG_DISPLAY: true,
		SAVEQUERIES: true,
		SCRIPT_DEBUG: true,
	},
	mappings: {
		"wp-cli.yml": "./wp-cli.wp-env.yml",
	},
	lifecycleScripts: {
		afterClean: runAfterClean.join(" && "),
		afterStart: runAfterStart.join(" && "),
	},
};

fs.writeFileSync(
	path.join(__dirname, ".wp-env.json"),

	JSON.stringify(wpEnvConfig, null, 2),
);

console.log("Generated .wp-env.json");
