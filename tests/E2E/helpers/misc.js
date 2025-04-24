const path = require('path');
const dotenv = require('dotenv');

function loadEnv(envPath = path.resolve(process.env.INIT_CWD || process.cwd(), '.env')) {
	dotenv.config({ path: envPath });
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

module.exports = { loadEnv, sleep };
