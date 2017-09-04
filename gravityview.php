<?php
/**
 * Plugin Name:       	GravityView
 * Plugin URI:        	https://gravityview.co
 * Description:       	The best, easiest way to display Gravity Forms entries on your website.
 * Version:          	1.22
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
define( 'GV_MIN_GF_VERSION', '1.9.14' );

/**
 * GravityView requires at least this version of WordPress to function properly.
 * @since 1.12
 */
define( 'GV_MIN_WP_VERSION', '4.0' );

/**
 * GravityView requires at least this version of PHP to function properly.
 * @since 1.12
 */
define( 'GV_MIN_PHP_VERSION', '5.2.4' );

/**
 * GravityView will require this version of PHP soon. False if no future PHP version changes are planned.
 * @since 1.19.2
 * @var string|false
 */
define( 'GV_FUTURE_MIN_PHP_VERSION', '5.3' );

/**
 * GravityView will soon require at least this version of Gravity Forms to function properly.
 * @since 1.19.4
 */
define( 'GV_FUTURE_MIN_GF_VERSION', '2.0.0-rc-1' );

/** Register hooks that are fired when the plugin is activated and deactivated. */
register_activation_hook( __FILE__, array( 'GravityView_Plugin', 'activate' ) );

register_deactivation_hook( __FILE__, array( 'GravityView_Plugin', 'deactivate' ) );

/**
 * The future is here and now... perhaps.
 */
require GRAVITYVIEW_DIR . 'future/loader.php';

/**
 * GravityView_Plugin main class.
 */
final class GravityView_Plugin {

	const version = '1.22';

	private static $instance;

	/**
	 * Singleton instance
	 *
	 * @return GravityView_Plugin   GravityView_Plugin object
	 */
	public static function getInstance() {

		if( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function __construct() {

		self::require_files();

		if( ! GravityView_Compatibility::is_valid() ) {
			return;
		}

		$this->include_files();

		$this->add_hooks();
	}

	/**
	 * Include files that are required by the plugin
	 * @since 1.18
	 */
	private static function require_files() {
		require_once( GRAVITYVIEW_DIR . 'includes/helper-functions.php' );
		require_once( GRAVITYVIEW_DIR . 'includes/class-common.php');
		require_once( GRAVITYVIEW_DIR . 'includes/connector-functions.php');
		require_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-compatibility.php' );
		require_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-roles-capabilities.php' );
		require_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-admin-notices.php' );
		require_once( GRAVITYVIEW_DIR . 'includes/class-admin.php' );
		require_once( GRAVITYVIEW_DIR . 'includes/class-post-types.php');
		require_once( GRAVITYVIEW_DIR . 'includes/class-cache.php');
	}

	/**
	 * Add hooks to set up the plugin
	 *
	 * @since 1.12
	 */
	private function add_hooks() {
		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ), 1 );

		// Load frontend files
		add_action( 'init', array( $this, 'frontend_actions' ), 20 );
	}

	/**
	 * Include global plugin files
	 *
	 * @since 1.12
	 */
	public function include_files() {

		// Load fields
		include_once( GRAVITYVIEW_DIR . 'includes/fields/class-gravityview-fields.php' );
		include_once( GRAVITYVIEW_DIR . 'includes/fields/class-gravityview-field.php' );

		// Load all field files automatically
		foreach ( glob( GRAVITYVIEW_DIR . 'includes/fields/class-gravityview-field*.php' ) as $gv_field_filename ) {
			include_once( $gv_field_filename );
		}

		include_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-entry-approval-status.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-gravityview-entry-approval.php' );

		include_once( GRAVITYVIEW_DIR .'includes/class-gravityview-entry-notes.php' );
		include_once( GRAVITYVIEW_DIR .'includes/load-plugin-and-theme-hooks.php' );

		// Load Extensions
		// @todo: Convert to a scan of the directory or a method where this all lives
		include_once( GRAVITYVIEW_DIR .'includes/extensions/edit-entry/class-edit-entry.php' );
		include_once( GRAVITYVIEW_DIR .'includes/extensions/delete-entry/class-delete-entry.php' );
		include_once( GRAVITYVIEW_DIR .'includes/extensions/entry-notes/class-gravityview-field-notes.php' );

		// Load WordPress Widgets
		include_once( GRAVITYVIEW_DIR .'includes/wordpress-widgets/register-wordpress-widgets.php' );

		// Load GravityView Widgets
		include_once( GRAVITYVIEW_DIR .'includes/widgets/register-gravityview-widgets.php' );

		// Add oEmbed
		include_once( GRAVITYVIEW_DIR . 'includes/class-oembed.php' );

		// Add logging
		include_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-logging.php' );

		include_once( GRAVITYVIEW_DIR . 'includes/class-ajax.php' );
		include_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-settings.php' );
		include_once( GRAVITYVIEW_DIR . 'includes/class-frontend-views.php' );
		include_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-admin-bar.php' );
		include_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-entry-list.php' );
		include_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-merge-tags.php'); /** @since 1.8.4 */
		include_once( GRAVITYVIEW_DIR . 'includes/class-data.php' );
		include_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-shortcode.php' );
		include_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-entry-link-shortcode.php' );
		include_once( GRAVITYVIEW_DIR . 'includes/class-gvlogic-shortcode.php' );
		include_once( GRAVITYVIEW_DIR . 'includes/presets/register-default-templates.php' );

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
	 * @return void
	 */
	public static function activate() {

		/**
		 * Do not allow activation if PHP version is lower than 5.3.
		 */
		$version = phpversion();
		if ( version_compare( $version, '5.3', '<' ) ) {

			if ( php_sapi_name() == 'cli' ) {
				printf( __( "GravityView requires PHP Version %s or newer. You're using Version %s. Please ask your host to upgrade your server's PHP.", 'gravityview' ),
					GV_FUTURE_MIN_PHP_VERSION , phpversion() );
			} else {
				printf( '<body style="padding: 0; margin: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen-Sans, Ubuntu, Cantarell, \'Helvetica Neue\', sans-serif;">' );
				printf( '<img src="' . plugins_url( 'assets/images/astronaut-200x263.png', GRAVITYVIEW_FILE ) . '" alt="The GravityView Astronaut Says:" style="float: left; height: 60px; margin-right : 10px;" />' );
				printf( __( "%sGravityView requires PHP Version %s or newer.%s \n\nYou're using Version %s. Please ask your host to upgrade your server's PHP.", 'gravityview' ),
					'<h3 style="font-size:16px; margin: 0 0 8px 0;">', GV_FUTURE_MIN_PHP_VERSION , "</h3>\n\n", $version );
				printf( '</body>' );
			}

			exit; /** Die without activating. Sorry. */
		}

		self::require_files();

		/** Deprecate in favor of \GV\View::register_post_type. */
		if ( ! defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
			// register post types
			GravityView_Post_Types::init_post_types();
		}

		/** Deprecate in favor of \GV\View::add_rewrite_endpoint. */
		if ( ! defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
			// register rewrite rules
			GravityView_Post_Types::init_rewrite();
		}

		/** Deprecate. Handled in \GV\Plugin::activate now. */
		if ( ! defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
			flush_rewrite_rules();

			// Update the current GV version
			update_option( 'gv_version', self::version );
		}

		// Add the transient to redirect to configuration page
		set_transient( '_gv_activation_redirect', true, 60 );

		// Clear settings transient
		delete_transient( 'gravityview_edd-activate_valid' );

		GravityView_Roles_Capabilities::get_instance()->add_caps();
	}


	/**
	 * Plugin deactivate function.
	 *
	 * @access public
	 * @deprecated
	 * @return void
	 */
	public static function deactivate() {
		if ( ! defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
			flush_rewrite_rules();
		}
	}

	/**
	 * Include the extension class
	 *
	 * @since 1.5.1
	 * @return void
	 */
	public static function include_extension_framework() {
		if ( ! class_exists( 'GravityView_Extension' ) ) {
			require_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-extension.php' );
		}
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

		$loaded = load_plugin_textdomain( 'gravityview', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		
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
	 * @deprecated
	 * @see \GV\Frontend_Request::is_admin via gravityview()->request->is_admin()
	 * @return bool
	 */
	public static function is_admin() {

		if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
			return gravityview()->request->is_admin();
		}

		$doing_ajax = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;

		return is_admin() && ! $doing_ajax;
	}

	/**
	 * Function to launch frontend objects
	 *
	 * @since 1.17 Added $force param
	 *
	 * @access public
	 *
	 * @param bool $force Whether to force loading, even if GravityView_Plugin::is_admin() returns true
	 *
	 * @return void
	 */
	public function frontend_actions( $force = false ) {

		if( self::is_admin() && ! $force ) { return; }

		include_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-image.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-template.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-api.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-frontend-views.php' );
		include_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-change-entry-creator.php' );


        /**
         * When an entry is created, check if we need to update the custom slug meta
         * todo: move this to its own class..
         */
        add_action( 'gform_entry_created', array( 'GravityView_API', 'entry_create_custom_slug' ), 10, 2 );

		/**
		 * @action `gravityview_include_frontend_actions` Triggered after all GravityView frontend files are loaded
		 *
		 * Nice place to insert extensions' frontend stuff
		 */
		do_action( 'gravityview_include_frontend_actions' );
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
		);

		/**
		 * @filter `gravityview_widget_active_areas` Array of zones available for widgets to be dropped into
		 * @param array $default_areas Definition for default widget areas
		 */
		return apply_filters( 'gravityview_widget_active_areas', $default_areas );
	}

	/** DEBUG */

    /**
     * Logs messages using Gravity Forms logging add-on
     * @param  string $message log message
     * @param mixed $data Additional data to display
     * @return void
     */
    public static function log_debug( $message, $data = null ){
	    /**
	     * @action `gravityview_log_debug` Log a debug message that shows up in the Gravity Forms Logging Addon and also the Debug Bar plugin output
	     * @param string $message Message to display
	     * @param mixed $data Supporting data to print alongside it
	     */
    	do_action( 'gravityview_log_debug', $message, $data );
    }

    /**
     * Logs messages using Gravity Forms logging add-on
     * @param  string $message log message
     * @return void
     */
    public static function log_error( $message, $data = null ){
	    /**
	     * @action `gravityview_log_error` Log an error message that shows up in the Gravity Forms Logging Addon and also the Debug Bar plugin output
	     * @param string $message Error message to display
	     * @param mixed $data Supporting data to print alongside it
	     */
    	do_action( 'gravityview_log_error', $message, $data );
    }

} // end class GravityView_Plugin

add_action('plugins_loaded', array('GravityView_Plugin', 'getInstance'), 1);
