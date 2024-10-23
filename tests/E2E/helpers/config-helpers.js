const fs = require('fs');
const path = require('path');

/**
 * Reads and validates plugins from a local config file.
 * @param {string} configFilePath - The path to the local config file.
 * @returns {string[]} An array of valid plugin paths, or an empty array if the file is invalid or does not exist.
 */
function getLocalPlugins(configFilePath) {
  function isValidPluginList(plugins) {
    return Array.isArray(plugins) && plugins.every(plugin => typeof plugin === 'string');
  }

  let localPlugins = [];
  try {
    if (fs.existsSync(configFilePath)) {
      const localConfig = JSON.parse(fs.readFileSync(configFilePath, 'utf-8'));
      if (isValidPluginList(localConfig.plugins)) {
        localPlugins = localConfig.plugins;
      } else {
        console.warn('Invalid plugin list in local-plugins.json. Ignoring local plugins.');
      }
    }
  } catch (error) {
    console.error('Error reading local-plugins.json:', error);
  }

  return localPlugins;
}

module.exports = {
  getLocalPlugins,
};
