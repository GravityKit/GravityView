<?php
/**
 * The GravityView plugin
 *
 * Create directories based on a Gravity Forms form, insert them using a shortcode, and modify how they output.
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @wordpress-plugin
 * Plugin Name:       	GravityView
 * Plugin URI:        	http://gravityview.co
 * Description:       	Create directories based on a Gravity Forms form, insert them using a shortcode, and modify how they output.
 * Version:          	1.7.6.1
 * Author:            	Katz Web Services, Inc.
 * Author URI:        	http://www.katzwebservices.com
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
if( !defined('GRAVITYVIEW_FILE') ) {
	/** @define "GRAVITYVIEW_FILE" "./gravityview.php" */
	define( 'GRAVITYVIEW_FILE', __FILE__ );
}

if ( !defined('GRAVITYVIEW_URL') ) {
	define( 'GRAVITYVIEW_URL', plugin_dir_url( __FILE__ ) );
}

if ( !defined('GRAVITYVIEW_DIR') ) {
	/** @define "GRAVITYVIEW_DIR" "./" */
	define( 'GRAVITYVIEW_DIR', plugin_dir_path( __FILE__ ) );
}

if ( !defined('GV_MIN_GF_VERSION') ) {
	/**
	 * GravityView requires at least this version of Gravity Forms to function properly.
	 */
	define( 'GV_MIN_GF_VERSION', '1.8' );
}

/** Load common & connector functions */
require_once( GRAVITYVIEW_DIR . 'includes/class-common.php');
require_once( GRAVITYVIEW_DIR . 'includes/connector-functions.php');

/** Register Post Types and Rewrite Rules */
require_once( GRAVITYVIEW_DIR . 'includes/class-post-types.php');

/** Add Cache Class */
require_once( GRAVITYVIEW_DIR . 'includes/class-cache.php');

/** Register hooks that are fired when the plugin is activated and deactivated. */
if( is_admin() ) {
	register_activation_hook( __FILE__, array( 'GravityView_Plugin', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'GravityView_Plugin', 'deactivate' ) );
}

/**
 * GravityView_Plugin main class.
 */
final class GravityView_Plugin {

	const version = '1.7.6.1';

	public static $theInstance;

	/**
	 * Singleton instance
	 *
	 * @return GravityView_Plugin   GravityView_Plugin object
	 */
	public static function getInstance() {

		if( empty( self::$theInstance ) ) {
			self::$theInstance = new GravityView_Plugin;
		}

		return self::$theInstance;
	}

	private function __construct() {

		require_once( GRAVITYVIEW_DIR .'includes/class-admin.php' );

		// If Gravity Forms doesn't exist or is outdated, load the admin view class to
		// show the notice, but not load any post types or process shortcodes.
		// Without Gravity Forms, there is no GravityView. Beautiful, really.
		if( !class_exists('GFForms') || false === version_compare(GFCommon::$version, GV_MIN_GF_VERSION, ">=") ) {

			// If the plugin's not loaded, might as well hide the shortcode for people.
			add_shortcode( 'gravityview', '__return_null' );

			return;
		}

		// Load Extensions
 		// @todo: Convert to a scan of the directory or a method where this all lives
		include_once( GRAVITYVIEW_DIR .'includes/extensions/edit-entry/class-edit-entry.php' );
		include_once( GRAVITYVIEW_DIR .'includes/extensions/delete-entry/class-delete-entry.php' );

		// Load WordPress Widgets
		include_once( GRAVITYVIEW_DIR .'includes/wordpress-widgets/register-wordpress-widgets.php' );

		// Load GravityView Widgets
		include_once( GRAVITYVIEW_DIR .'includes/widgets/register-gravityview-widgets.php' );

		// Add oEmbed
		include_once( GRAVITYVIEW_DIR . 'includes/class-oembed.php' );

		// Add logging
		include_once( GRAVITYVIEW_DIR . 'includes/class-logging.php');

		include_once( GRAVITYVIEW_DIR . 'includes/class-ajax.php' );
		include_once( GRAVITYVIEW_DIR . 'includes/class-settings.php');
		include_once( GRAVITYVIEW_DIR . 'includes/class-frontend-views.php' );
		include_once( GRAVITYVIEW_DIR . 'includes/helper-functions.php' );
		include_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-entry-list.php' );
		include_once( GRAVITYVIEW_DIR . 'includes/class-data.php' );
		include_once( GRAVITYVIEW_DIR . 'includes/class-gvlogic-shortcode.php' );

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ), 1 );

		// Load frontend files
		add_action( 'init', array( $this, 'frontend_actions' ), 20 );

		// Load default templates
		add_action( 'init', array( $this, 'register_default_templates' ), 11 );

	}

	/**
	 * Check whether GravityView is network activated
	 * @since 1.7.6
	 * @return bool
	 */
	public static function is_network_activated() {
		return is_multisite() && ( function_exists('is_plugin_active_for_network') && is_plugin_active_for_network( 'gravityview/gravityview.php' ) );
	}


	/**
	 * Plugin activate function.
	 *
	 * @access public
	 * @static
	 * @param mixed $network_wide
	 * @return void
	 */
	public static function activate( $network_wide ) {

		// register post types
		GravityView_Post_Types::init_post_types();

		// register rewrite rules
		GravityView_Post_Types::init_rewrite();

		flush_rewrite_rules();

		// Update the current GV version
		update_option( 'gv_version', self::version );

		// Add the transient to redirect to configuration page
		set_transient( '_gv_activation_redirect', true, 60 );

		// Clear settings transient
		delete_transient( 'redux_edd_license_license_valid' );
	}


	/**
	 * Plugin deactivate function.
	 *
	 * @access public
	 * @static
	 * @param mixed $network_wide
	 * @return void
	 */
	public static function deactivate( $network_wide ) {

		flush_rewrite_rules();

	}

	/**
	 * Include the extension class
	 *
	 * @since 1.5.1
	 * @return void
	 */
	public static function include_extension_framework() {
	    require_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-extension.php' );
	}

	/**
	 * Load GravityView_Widget class
	 *
	 * @since 1.7.5.1
	 */
	public static function include_widget_class() {
		include_once( GRAVITYVIEW_DIR .'includes/widgets/class-gravityview-widget.php' );
	}


	/**
	 * Loads the plugin's translated strings.
	 *
	 * @access public
	 * @return void
	 */
	public function load_plugin_textdomain() {

		$loaded = load_plugin_textdomain( 'gravityview', false, '/languages/' );
		if ( ! $loaded ) {
			$loaded = load_muplugin_textdomain( 'gravityview', '/languages/' );
		}
		if ( ! $loaded ) {
			$loaded = load_theme_textdomain( 'gravityview', '/languages/' );
		}
		if ( ! $loaded ) {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'gravityview' );
			$mofile = dirname( __FILE__ ) . '/languages/gravityview-'. $locale .'.mo';
			load_textdomain( 'gravityview', $mofile );
		}

	}

	/**
	 * Check if is_admin(), and make sure not DOING_AJAX
	 * @since 1.7.5
	 * @return bool
	 */
	public static function is_admin() {

		$doing_ajax = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;

		return is_admin() && ! $doing_ajax;
	}

	/**
	 * Function to launch frontend objects
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_actions() {

		if( self::is_admin() ) { return; }

		include_once( GRAVITYVIEW_DIR .'includes/class-image.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-template.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-api.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-frontend-views.php' );
		include_once( GRAVITYVIEW_DIR . 'includes/class-change-entry-creator.php' );

		// Nice place to insert extensions' frontend stuff
		do_action('gravityview_include_frontend_actions');
	}

	/**
	 * Registers the default templates
	 * @todo Move somehere logical
	 * @return void
	 */
	function register_default_templates() {
		include_once( GRAVITYVIEW_DIR .'includes/default-templates.php' );
	}

	/**
	 * helper function to define the default widget areas
	 * @todo Move somewhere logical
	 * @return array definition for default widget areas
	 */
	public static function get_default_widget_areas() {
		$default_areas = array(
			array( '1-1' => array( array( 'areaid' => 'top', 'title' => __('Top', 'gravityview' ) , 'subtitle' => '' ) ) ),
			array( '1-2' => array( array( 'areaid' => 'left', 'title' => __('Left', 'gravityview') , 'subtitle' => '' ) ), '2-2' => array( array( 'areaid' => 'right', 'title' => __('Right', 'gravityview') , 'subtitle' => '' ) ) ),
			//array( '1-1' => array( 	array( 'areaid' => 'bottom', 'title' => __('Full Width Bottom', 'gravityview') , 'subtitle' => '' ) ) )
		);

		return apply_filters( 'gravityview_widget_active_areas', $default_areas );
	}

	/** DEBUG */

    /**
     * Logs messages using Gravity Forms logging add-on
     * @param  string $message log message
     * @return void
     */
    public static function log_debug( $message, $data = null ){
    	do_action( 'gravityview_log_debug', $message, $data );
    }

    /**
     * Logs messages using Gravity Forms logging add-on
     * @param  string $message log message
     * @return void
     */
    public static function log_error( $message, $data = null ){
    	do_action( 'gravityview_log_error', $message, $data );
    }

} // end class GravityView_Plugin

add_action('plugins_loaded', array('GravityView_Plugin', 'getInstance'), 1);
