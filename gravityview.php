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
 * Version:          	1.0.7-beta
 * Author:            	Katz Web Services, Inc.
 * Author URI:        	http://www.katzwebservices.com
 * Text Domain:       	gravity-view
 * License:           	GPLv2 or later
 * License URI: 		http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path:			/languages
 * GitHub Plugin URI: 	ToBeDefined
 */

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/** Constants */
if( !defined('GRAVITYVIEW_FILE') )
	define( 'GRAVITYVIEW_FILE', __FILE__ );

if ( !defined('GRAVITYVIEW_URL') )
	define( 'GRAVITYVIEW_URL', plugin_dir_url( __FILE__ ) );

if ( !defined('GRAVITYVIEW_DIR') )
	define( 'GRAVITYVIEW_DIR', plugin_dir_path( __FILE__ ) );


if ( !defined('GV_MIN_GF_VERSION') ) {
	/**
	 * GravityView requires at least this version of Gravity Forms to function properly.
	 */
	define( 'GV_MIN_GF_VERSION', '1.8' );
}

/** Register hooks that are fired when the plugin is activated and deactivated. */
if( is_admin() ) {
	register_activation_hook( __FILE__, array( 'GravityView_Plugin', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'GravityView_Plugin', 'deactivate' ) );
}

/** Load connector functions */
require_once( GRAVITYVIEW_DIR . 'includes/connector-functions.php');

/**
 * GravityView_Plugin main class.
 */
final class GravityView_Plugin {

	const version = '1.0.7-beta';

	public static $theInstance;

	/**
	 * Singleton instance
	 *
	 * @return GravityView_Plugin   GravityView_Plugin object
	 */
	public static function getInstance() {

		if(!empty(self::$theInstance)) {
			return self::$theInstance;
		}

		return new GravityView_Plugin;
	}

	public function __construct() {

		require_once( GRAVITYVIEW_DIR .'includes/class-admin.php' );

		// If Gravity Forms doesn't exist or is outdated, load the admin view class to
		// show the notice, but not load any post types or process shortcodes.
		// Without Gravity Forms, there is no GravityView. Beautiful, really.
		if( !class_exists('GFForms') || false === version_compare(GFCommon::$version, GV_MIN_GF_VERSION, ">=") ) {

			// If the plugin's not loaded, might as well hide the shortcode for people.
			add_shortcode( 'gravityview', '__return_null' );

			return;
		}


		self::$theInstance = $this;

		// Add logging
		require_once( GRAVITYVIEW_DIR . 'includes/class-logging.php');

		require_once( GRAVITYVIEW_DIR . 'includes/class-ajax.php' );
		require_once( GRAVITYVIEW_DIR . 'includes/class-settings.php');
		include_once( GRAVITYVIEW_DIR .'includes/class-frontend-views.php' );

		// Load Extensions
		// TODO: Convert to a scan of the directory or a method where this all lives
		include_once( GRAVITYVIEW_DIR . 'includes/extensions/datatables/ext-datatables.php');
		include_once( GRAVITYVIEW_DIR .'includes/extensions/edit-entry/class-edit-entry.php' );

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Load custom post types. It's a static method.
		add_action( 'init', array( 'GravityView_Plugin', 'init_setup' ) );


		if( ! is_admin() ) {
			add_action( 'init', array( $this, 'frontend_actions' ), 20 );
		}


		// Load default templates
		add_action( 'gravityview_init', array( $this, 'register_default_templates' ) );

		// Load default widgets
		add_action( 'gravityview_init', array( $this, 'register_default_widgets' ) );

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

		self::init_setup();

		flush_rewrite_rules();

		// Add "Upgraded From" Option
		$current_version = get_option( 'gv_version' );
		if ( $current_version ) {
			update_option( 'gv_version_upgraded_from', $current_version );
		}

		// Update the current GV version
		update_option( 'gv_version', self::version );

		// Add the transient to redirect to configuration page
		set_transient( '_gv_activation_redirect', true, 30 );
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
	 * Loads the plugin's translated strings.
	 *
	 * @access public
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'gravity-view', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}


	/**
	 * Modify plugin action links at plugins screen
	 *
	 * @access public
	 * @static
	 * @param mixed $links
	 * @return void
	 */
	public static function plugin_action_links( $links ) {
		$support_link = 'https://katzwebservices.zendesk.com/hc/en-us/categories/200136096';
		$action = array( '<a href="' . $support_link . '">'. esc_html__( 'Support', 'gravity-view' ) .'</a>' );
		return array_merge( $action, $links );
	}

	/**
	 * Get text for no views found.
	 * @todo Move somewhere appropriate.
	 * @return string HTML message with no container tags.
	 */
	static function no_views_text() {
		// Floaty the astronaut
		$image = '<img src="'.plugins_url( 'images/astronaut-200x263.png', GRAVITYVIEW_FILE ).'" class="alignleft" height="87" width="66" alt="The GravityView Astronaut Says:" style="margin:0 10px 10px 0;" />';

		$not_found =  sprintf( esc_attr__("%sYou don't have any active views. Let&rsquo;s go %screate one%s!%s\n\nIf you feel like you're lost in space and need help getting started, check out the %sGetting Started%s page.", 'gravity-view' ), '<h3>', '<a href="'.admin_url('post-new.php?post_type=gravityview').'">', '</a>', '</h3>', '<a href="'.admin_url( 'edit.php?post_type=gravityview&page=gv-getting-started' ).'">', '</a>' );

		return $image.wpautop( $not_found );
	}

	/**
	 * Init plugin components such as register own custom post types
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function init_setup() {

		//Register Custom Post Type - gravityview
		$labels = array(
			'name'                => _x( 'Views', 'Post Type General Name', 'gravity-view' ),
			'singular_name'       => _x( 'View', 'Post Type Singular Name', 'gravity-view' ),
			'menu_name'           => _x( 'Views', 'Menu name', 'gravity-view' ),
			'parent_item_colon'   => __( 'Parent View:', 'gravity-view' ),
			'all_items'           => __( 'All Views', 'gravity-view' ),
			'view_item'           => _x( 'View', 'View Item', 'gravity-view' ),
			'add_new_item'        => __( 'Add New View', 'gravity-view' ),
			'add_new'             => __( 'New View', 'gravity-view' ),
			'edit_item'           => __( 'Edit View', 'gravity-view' ),
			'update_item'         => __( 'Update View', 'gravity-view' ),
			'search_items'        => __( 'Search Views', 'gravity-view' ),
			'not_found'           => self::no_views_text(),
			'not_found_in_trash'  => __( 'No Views found in Trash', 'gravity-view' ),
		);
		$args = array(
			'label'               => __( 'view', 'gravity-view' ),
			'description'         => __( 'Create views based on a Gravity Forms form', 'gravity-view' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'genesis-layouts'),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 17,
			'menu_icon'           => '',
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'rewrite'             => array(
				'slug' => apply_filters( 'gravityview_slug', 'view' )
			),
			'capability_type'     => 'page',
		);

		register_post_type( 'gravityview', $args );

		// Hook for other init scripts
		do_action( 'gravityview_init' );
	}

	/**
	 * Function to launch frontend objects
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_actions() {

		include_once( GRAVITYVIEW_DIR .'includes/class-image.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-template.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-api.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-frontend-views.php' );

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
	 * Register the default widgets
	 * @todo Move somehere logical
	 * @return void
	 */
	function register_default_widgets() {
		include_once( GRAVITYVIEW_DIR .'includes/default-widgets.php' );
	}

	/**
	 * helper function to define the default widget areas
	 * @todo Move somehere logical
	 * @return array definition for default widget areas
	 */
	public static function get_default_widget_areas() {
		$default_areas = array(
			array( '1-1' => array( array( 'areaid' => 'top', 'title' => __('Top', 'gravity-view' ) , 'subtitle' => '' ) ) ),
			array( '1-2' => array( array( 'areaid' => 'left', 'title' => __('Left', 'gravity-view') , 'subtitle' => '' ) ), '2-2' => array( array( 'areaid' => 'right', 'title' => __('Right', 'gravity-view') , 'subtitle' => '' ) ) ),
			//array( '1-1' => array( 	array( 'areaid' => 'bottom', 'title' => __('Full Width Bottom', 'gravity-view') , 'subtitle' => '' ) ) )
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
