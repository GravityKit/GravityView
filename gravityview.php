<?php
/**
 * Plugin Name:         GravityView
 * Plugin URI:          https://www.gravitykit.com
 * Description:         The best, easiest way to display Gravity Forms entries on your website.
 * Version:             2.31.1
 * Requires PHP:        7.4.0
 * Author:              GravityKit
 * Author URI:          https://www.gravitykit.com
 * Text Domain:         gk-gravityview
 * License:             GPLv2 or later
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.html
 */

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

require_once __DIR__ . '/vendor_prefixed/gravitykit/foundation/src/preflight_check.php';

if ( ! GravityKit\GravityView\Foundation\should_load( __FILE__ ) ) {
	return;
}

if ( ! GravityKit\GravityView\Foundation\meets_min_php_version_requirement( __FILE__, '7.4.0' ) ) {
	return;
}

/** Constants */

/**
 * The plugin version.
 */
define( 'GV_PLUGIN_VERSION', '2.31.1' );

/**
 * Full path to the GravityView file
 *
 * @define "GRAVITYVIEW_FILE" "./gravityview.php"
 */
define( 'GRAVITYVIEW_FILE', __FILE__ );

/**
 * The URL to this file, with trailing slash
 */
define( 'GRAVITYVIEW_URL', plugin_dir_url( __FILE__ ) );


/** @define "GRAVITYVIEW_DIR" "./" The absolute path to the plugin directory, with trailing slash */
define( 'GRAVITYVIEW_DIR', plugin_dir_path( __FILE__ ) );

/**
 * GravityView requires at least this version of Gravity Forms to function properly.
 */
define( 'GV_MIN_GF_VERSION', '2.6.0' );

/**
 * GravityView will soon require at least this version of Gravity Forms to function properly.
 *
 * @since 1.19.4
 */
define( 'GV_FUTURE_MIN_GF_VERSION', '2.7.0' );

/**
 * GravityView requires at least this version of WordPress to function properly.
 *
 * @since 1.12
 */
define( 'GV_MIN_WP_VERSION', '4.7.0' );

/**
 * GravityView will soon require at least this version of WordPress to function properly.
 *
 * @since 2.9.3
 */
define( 'GV_FUTURE_MIN_WP_VERSION', '5.3' );

/**
 * GravityView will require this version of PHP soon. False if no future PHP version changes are planned.
 *
 * @since 1.19.2
 * @var string|false
 */
define( 'GV_FUTURE_MIN_PHP_VERSION', '8.0.0' );

/**
 * The future is here and now.
 */
require GRAVITYVIEW_DIR . 'future/loader.php';

add_action(
	'plugins_loaded',
	function () {
		/**
		 * GravityView_Plugin is only used by the legacy class-gravityview-extension.php that's shipped with extensions.
		 *
		 * @TODO Remove once all extensions have been updated to use Foundation.
		 */
		final class GravityView_Plugin {
			const version = GV_PLUGIN_VERSION;
		}
	},
	5
);
