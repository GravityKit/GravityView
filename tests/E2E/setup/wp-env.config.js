// This creates a .wp-env.json file using the .env file in the root of the project.

const fs = require('fs');
const path = require('path');
const { getLocalPlugins } = require('../helpers/config-helpers');

require('dotenv').config({ path: `${process.env.INIT_CWD}/.env` });

const localConfigPath = path.join(__dirname, '.local-plugins.json');

const localPlugins = getLocalPlugins(localConfigPath);

const wpEnvConfig = {
  phpVersion: '7.2',
  plugins: [
    '../../..',
    process.env.GRAVITY_FORMS_FOLDER || '/tmp/gravityforms',
    ...localPlugins,
  ],
  port: parseInt(process.env.WP_ENV_PORT, 10) || 8888,
  config: {
    WP_DEBUG: true,
    WP_DEBUG_LOG: true,
    WP_DEBUG_DISPLAY: true,
    SAVEQUERIES: true,
    SCRIPT_DEBUG: true,
  },
};

fs.writeFileSync(
  path.join(__dirname, '.wp-env.json'),

  JSON.stringify(wpEnvConfig, null, 2)
);

console.log('Generated .wp-env.json');