<?php
/**
 * Plugin Name:       	GravityView
 * Plugin URI:        	https://gravityview.co
 * Description:       	The best, easiest way to display Gravity Forms entries on your website.
 * Version:          	2.0.14.1
 * Author:            	GravityView
 * Author URI:        	https://gravityview.co
 * Text Domain:       	gravityview
 * License:           	GPLv2 or later
 * License URI: 		http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path:			/languages
 */

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/** Constants */

/**
 * The plugin version.
 */
define( 'GV_PLUGIN_VERSION', '2.0.14.1' );

/**
 * Full path to the GravityView file
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
define( 'GV_MIN_GF_VERSION', '2.1' );

/**
 * GravityView requires at least this version of WordPress to function properly.
 * @since 1.12
 */
define( 'GV_MIN_WP_VERSION', '4.4' );

/**
 * GravityView requires at least this version of PHP to function properly.
 * @since 1.12
 */
define( 'GV_MIN_PHP_VERSION', '5.3.0' );

/**
 * GravityView will require this version of PHP soon. False if no future PHP version changes are planned.
 * @since 1.19.2
 * @var string|false
 */
define( 'GV_FUTURE_MIN_PHP_VERSION', '5.4' );

/**
 * GravityView will soon require at least this version of Gravity Forms to function properly.
 * @since 1.19.4
 */
define( 'GV_FUTURE_MIN_GF_VERSION', '2.3.0' );

/**
 * The future is here and now.
 */
require GRAVITYVIEW_DIR . 'future/loader.php';

/**
 * GravityView_Plugin main class.
 *
 * @deprecated see `gravityview()->plugin` and `\GV\Plugin`
 */
final class GravityView_Plugin {

	/**
	 * @deprecated Use \GV\Plugin::$version
	 */
	const version = GV_PLUGIN_VERSION;

	private static $instance;

	/**
	 * Singleton instance
	 *
	 * @deprecated See \GV\Plugin
	 *
	 * @return GravityView_Plugin GravityView_Plugin object
	 */
	public static function getInstance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * @deprecated See \GV\Plugin
	 */
	private function __construct() {
		gravityview()->log->notice( '\GravityView_Plugin is deprecated. Use \GV\Plugin instead.' );
	}

	/**
	 * Include global plugin files
	 *
	 * @deprecated Use gravityview()->plugin->include_legacy_core
	 *
	 * @since 1.12
	 */
	public function include_files() {
		gravityview()->log->notice( '\GravityView_Plugin is deprecated. Use \GV\Plugin instead.' );
		gravityview()->plugin->include_legacy_core();
	}

	/**
	 * Check whether GravityView is network activated
	 *
	 * @deprecated See \GV\Plugin
	 *
	 * @since 1.7.6
	 * @return bool
	 */
	public static function is_network_activated() {
		gravityview()->log->notice( '\GravityView_Plugin is deprecated. Use \GV\Plugin instead.' );
		return gravityview()->plugin->is_network_activated();
	}


	/**
	 * Plugin activate function.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function activate() {
		gravityview()->log->notice( '\GravityView_Plugin is deprecated. Use \GV\Plugin instead.' );
	}


	/**
	 * Plugin deactivate function.
	 *
	 * @access public
	 * @deprecated see \GV\Plugin::deactivate()
	 * @return void
	 */
	public static function deactivate() {
		gravityview()->log->notice( '\GravityView_Plugin is deprecated. Use \GV\Plugin instead.' );
	}

	/**
	 * Include the extension class
	 *
	 * @deprecated The extension framework is included by default now.
	 *
	 * @since 1.5.1
	 * @return void
	 */
	public static function include_extension_framework() {
		gravityview()->log->notice( '\GravityView_Plugin is deprecated. Use \GV\Plugin instead.' );
	}

	/**
	 * Load GravityView_Widget class
	 *
	 * @deprecated The widget class is loaded elsewhere in legacy core.
	 *
	 * @since 1.7.5.1
	 */
	public static function include_widget_class() {
		gravityview()->log->notice( '\GravityView_Plugin is deprecated. Use \GV\Plugin instead.' );
	}


	/**
	 * Loads the plugin's translated strings.
	 *
	 * @deprecated Use \GV\Plugin::load_textdomain()
	 *
	 * @access public
	 * @return void
	 */
	public function load_plugin_textdomain() {
		gravityview()->log->notice( '\GravityView_Plugin is deprecated. Use \GV\Plugin instead.' );
		gravityview()->plugin->load_textdomain();
	}

	/**
	 * Check if is_admin(), and make sure not DOING_AJAX
	 * @since 1.7.5
	 * @deprecated
	 * @see \GV\Frontend_Request::is_admin via gravityview()->request->is_admin()
	 * @return bool
	 */
	public static function is_admin() {
		gravityview()->log->notice( '\GravityView_Plugin::is_admin() is deprecated. Use \GV\Request::is_admin() instead.' );
		return gravityview()->request->is_admin();
	}

	/**
	 * Function to launch frontend objects
	 *
	 * @since 1.17 Added $force param
	 *
	 * @access public
	 *
	 * @param bool $force Whether to force loading
	 *
	 * @return void
	 */
	public function frontend_actions( $force = false ) {
		gravityview()->log->notice( '\GravityView_Plugin is deprecated. Use \GV\Plugin instead.' );
		gravityview()->plugin->include_legacy_frontend( $force );
	}

	/**
	 * Helper function to define the default widget areas.
	 *
	 * @deprecated Moved to GravityView_Widget::get_default_widget_areas()
	 *
	 * @return array definition for default widget areas
	 */
	public static function get_default_widget_areas() {
		return GravityView_Widget::get_default_widget_areas();
	}

	/** DEBUG */

    /**
     * Logs messages using Gravity Forms logging add-on
     * @param  string $message log message
     * @param mixed $data Additional data to display
	 * @deprecated use gravityview()->log
     * @return void
     */
    public static function log_debug( $message, $data = null ){
		gravityview()->log->notice( '\GravityView_Plugin is deprecated. Use \GV\Plugin instead.' );
		gravityview()->log->debug( $message, $data );
    }

    /**
     * Logs messages using Gravity Forms logging add-on
     * @param  string $message log message
	 * @deprecated use gravityview()->log
     * @return void
     */
    public static function log_error( $message, $data = null ){
		gravityview()->log->notice( '\GravityView_Plugin is deprecated. Use \GV\Plugin instead.' );
		gravityview()->log->error( $message, $data );
    }
} // end class GravityView_Plugin
