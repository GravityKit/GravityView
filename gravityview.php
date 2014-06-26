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
 * Version:          	1.0.6-beta
 * Author:            	Katz Web Services, Inc.
 * Author URI:        	http://www.katzwebservices.com
 * Text Domain:       	gravity-view
 * License:           	GPLv2 or later
 * License URI: 		http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path:			/languages
 * GitHub Plugin URI: 	ToBeDefined
 */

/** If this file is called directly, abort. */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/** Constants */
if( !defined('GRAVITYVIEW_FILE') )
	define( 'GRAVITYVIEW_FILE', __FILE__ );
if ( !defined('GRAVITYVIEW_URL') )
	define( 'GRAVITYVIEW_URL', plugin_dir_url( __FILE__ ) );
if ( !defined('GRAVITYVIEW_DIR') )
	define( 'GRAVITYVIEW_DIR', plugin_dir_path( __FILE__ ) );


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

	const version = '1.0.6-beta';

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

		// If Gravity Forms doesn't exist or is outdated, load the admin view class to
		// show the notice, but not load any post types or process shortcodes.
		// Without Gravity Forms, there is no GravityView. Beautiful, really.
		if( !class_exists('GFForms') || false === version_compare(GFCommon::$version, '1.8', ">=") ) {
			require_once( GRAVITYVIEW_DIR .'includes/class-admin-views.php' );
			return;
		}

		self::$theInstance = $this;

		// Add logging
		require_once( GRAVITYVIEW_DIR . 'includes/class-logging.php');

		require_once( GRAVITYVIEW_DIR . 'includes/class-ajax.php' );
		require_once( GRAVITYVIEW_DIR . 'includes/class-settings.php');

		// Load Extensions
		// TODO: Convert to a scan of the directory or a method where this all lives
		include_once( GRAVITYVIEW_DIR . 'includes/extensions/datatables/ext-datatables.php');

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Load custom post types. It's a static method.
		add_action( 'init', array( 'GravityView_Plugin', 'init_setup' ) );


		if( is_admin() ) {

			// Enable Gravity Forms tooltips
			require_once( GFCommon::get_base_path() . '/tooltips.php' );

			require_once( GRAVITYVIEW_DIR . 'includes/admin/metaboxes.php' );

			// Filter Admin messages
			add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
			add_filter( 'bulk_post_updated_messages', array( $this, 'post_updated_messages' ) );

			add_filter( 'plugin_action_links_'. plugin_basename( __FILE__) , array( $this, 'plugin_action_links' ) );

			add_action( 'plugins_loaded', array( $this, 'backend_actions' ) );

			//Hooks for no-conflict functionality
		    add_action( 'wp_print_scripts', array( $this, 'no_conflict_scripts' ), 1000);
		    add_action( 'admin_print_footer_scripts', array( $this, 'no_conflict_scripts' ), 9);

		    add_action( 'wp_print_styles', array( $this, 'no_conflict_styles' ), 1000);
		    add_action( 'admin_print_styles', array( $this, 'no_conflict_styles' ), 1);
		    add_action( 'admin_print_footer_scripts', array( $this, 'no_conflict_styles' ), 1);
		    add_action( 'admin_footer', array( $this, 'no_conflict_styles' ), 1);


		} else {

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
	 * Init plugin components such as register own custom post types
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function init_setup() {

		// Floaty the astronaut
		$image = '<img src="'.plugins_url( 'images/astronaut-200x263.png', GRAVITYVIEW_FILE ).'" class="alignleft" height="87" width="66" alt="The GravityView Astronaut Says:" style="margin:0 10px 10px 0;" />';

		$not_found =  sprintf( __("%sYou don't have any active views. Let's go %screate one%s!%s\n\nIf you feel like you're lost in space and need help getting started, check out the %sGetting Started%s page.", 'gravity-view' ), '<h3>', '<a href="'.admin_url('post-new.php?post_type=gravityview').'">', '</a>', '</h3>', '<a href="'.admin_url( 'edit.php?post_type=gravityview&page=gv-getting-started' ).'">', '</a>' );

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
			'not_found'           => $image.$not_found,
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

		// Rewrite rules
		include_once( GRAVITYVIEW_DIR .'includes/class-frontend-views.php' );
		GravityView_frontend::init_rewrite();

		// Hook for other init scripts
		do_action( 'gravityview_init' );
	}

	/**
	 * Filter Admin messages
	 *
	 * @param  array      $messages Existing messages
	 * @return array                Messages with GravityView views!
	 */
	function post_updated_messages( $messages, $bulk_counts = NULL ) {
		global $post;

		$post_id = isset($_GET['post']) ? intval($_GET['post']) : NULL;

		// By default, there will only be one item being modified.
		// When in the `bulk_post_updated_messages` filter, there will be passed a number
		// of modified items that will override this array.
		$bulk_counts = is_null( $bulk_counts ) ? array( 'updated' => 1 , 'locked' => 1 , 'deleted' => 1 , 'trashed' => 1, 'untrashed' => 1 ) : $bulk_counts;

		$messages['gravityview'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => sprintf(__( 'View updated. %sView on website.%s', 'gravity-view' ), '<a href="'.get_permalink( $post_id ).'">', '</a>'),
			2  => sprintf(__( 'View updated. %sView on website.%s', 'gravity-view' ), '<a href="'.get_permalink( $post_id ).'">', '</a>'),
			3  => __( 'View deleted.', 'gravity-view' ),
			4  => sprintf(__( 'View updated. %sView on website.%s', 'gravity-view' ), '<a href="'.get_permalink( $post_id ).'">', '</a>'),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'View restored to revision from %s', 'gravity-view' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf(__( 'View published. %sView on website.%s', 'gravity-view' ), '<a href="'.get_permalink( $post_id ).'">', '</a>'),
			7  => sprintf(__( 'View saved. %sView on website.%s', 'gravity-view' ), '<a href="'.get_permalink( $post_id ).'">', '</a>'),
			8  => __( 'View submitted.', 'gravity-view' ),
			9  => sprintf(
				__( 'View scheduled for: <strong>%1$s</strong>.', 'gravity-view' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'gravity-view' ), strtotime( ( isset( $post->post_date ) ? $post->post_date : NULL ) ) )
			),
			10  => sprintf(__( 'View draft updated. %sView on website.%s', 'gravity-view' ), '<a href="'.get_permalink( $post_id ).'">', '</a>'),

			/**
			 * These apply to `bulk_post_updated_messages`
			 * @file wp-admin/edit.php
			 */
			'updated'   => _n( '%s View updated.', '%s Views updated.', $bulk_counts['updated'] ),
			'locked'    => _n( '%s View not updated, somebody is editing it.', '%s Views not updated, somebody is editing them.', $bulk_counts['locked'] ),
			'deleted'   => _n( '%s View permanently deleted.', '%s Views permanently deleted.', $bulk_counts['deleted'] ),
			'trashed'   => _n( '%s View moved to the Trash.', '%s Views moved to the Trash.', $bulk_counts['trashed'] ),
			'untrashed' => _n( '%s View restored from the Trash.', '%s Views restored from the Trash.', $bulk_counts['untrashed'] ),
		);

		return $messages;
	}


	/**
	 * Function to launch admin objects
	 *
	 * @access public
	 * @return void
	 */
	public function backend_actions() {

		include_once( GRAVITYVIEW_DIR .'includes/class-admin-views.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-welcome.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-add-shortcode.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-approve-entries.php' );

		include_once( GRAVITYVIEW_DIR .'includes/fields/class.field.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/created-by.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/date.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/entry-date.php' );

		// Nice place to insert extensions' backend stuff
		do_action('gravityview_include_backend_actions');
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
	 * @return void
	 */
	function register_default_templates() {
		include_once( GRAVITYVIEW_DIR .'includes/default-templates.php' );
	}

	/**
	 * Register the default widgets
	 * @return void
	 */
	function register_default_widgets() {
		include_once( GRAVITYVIEW_DIR .'includes/default-widgets.php' );
	}

	/**
	 * helper function to define the default widget areas
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

	/** no conflict mode functions */

	/**
	 * Checks if the current page is a GravityView page
	 *
	 * @param string $include_settings Should we check if it's the settings page as well?
	 * @return boolean|string page name or false
	 */
	static function is_gravityview_page($include_settings = false) {
		global $current_screen, $plugin_page;

		// If GravityView post type, but not the settings page, it's GravityView Page.
		if( !empty( $current_screen->post_type ) && 'gravityview' === $current_screen->post_type) {

			// Is this the settings page?
			if($plugin_page === 'settings') {
				// If we asked to include the settings page as a GV page, then return 'settings'.
				// Otherwise, return false.
				return $include_settings ? 'settings' : false;
			}

			return 'admin_views';
		}

		return false;
	}

	/**
	 * Callback to eliminate any non-registered script
	 * @todo  Move this to GravityView_Admin_Views
	 * @return void
	 */
	function no_conflict_scripts() {

		global $gravityview_settings;

		if( ! self::is_gravityview_page() || empty( $gravityview_settings['no-conflict-mode'] ) ) {
			return;
		}

		global $wp_scripts;

		$wp_required_scripts = array(
			'debug-bar-extender',
            'backcallsc',
            'common',
            'admin-bar',
            'debug-bar',
            'debug-bar-codemirror',
            'debug-bar-console',
            'puc-debug-bar-js',
            'autosave',
            'post',
            'utils',
            'svg-painter',
            'wp-auth-check',
            'heartbeat',
			'media-editor',
			'media-upload',
            'thickbox',
            'jquery-ui-dialog',
            'jquery-ui-tabs',
            'jquery-ui-draggable',
            'jquery-ui-droppable',

            // Redux Framework
            'select2-js',
            'qtip-js',
            'nouislider-js',
            'serializeForm-js',
            'ace-editor-js',
            'redux-vendor',
            'redux-js',
            'jquery',
            'jquery-ui-core',
            'jquery-ui-sortable',
            'jquery-ui-datepicker',
            'jquery-ui-dialog',
            'jquery-ui-slider',
            'wp-color-picker',
            'jquery-ui-accordion',
            );

		$this->remove_conflicts( $wp_scripts, $wp_required_scripts, 'scripts' );
	}

	/**
	 * Callback to eliminate any non-registered style
	 * @todo  Move this to GravityView_Admin_Views
	 * @return void
	 */
	function no_conflict_styles() {
		global $gravityview_settings, $wp_styles;

		if( ! self::is_gravityview_page() ) {
			return;
		}

		// Something's not right; the styles aren't registered.
		if( !empty( $wp_styles->registered ) )  {
			foreach ($wp_styles->registered as $key => $style) {
				if( preg_match( '/^(?:wp\-)?jquery/ism', $key ) ) {
					wp_dequeue_style( $key );
				}
			}
		}

		// Making sure jQuery is unset will be enough
		if( empty( $gravityview_settings['no-conflict-mode'] ) ) {
			return;
		}

        $wp_required_styles = array(
        	'debug-bar-extender',
	        'admin-bar',
	        'debug-bar',
	        'debug-bar-codemirror',
	        'debug-bar-console',
	        'puc-debug-bar-style',
	        'colors',
	        'ie',
	        'wp-auth-check',
	        'media-views',
			'thickbox',
			'dashicons',
	        'wp-jquery-ui-dialog',
	        'jquery-ui-sortable',

	        // Redux Framework
	        'redux-css',
	        'redux-elusive-icon',
	        'redux-elusive-icon-ie7',
	        'select2-css',
	        'qtip-css',
	        'nouislider-css',
	        'jquery-ui-css',
	        'redux-rtl-css',
	        'wp-color-picker',
	    );

		$this->remove_conflicts( $wp_styles, $wp_required_styles, 'styles' );

		// Allow settings, etc, to hook in after
		do_action('gravityview_remove_conflicts_after');
	}

	/**
	 * Remove any style or script non-registered in the no conflict mode
	 * @todo  Move this to GravityView_Admin_Views
	 * @param  object $wp_objects        Object of WP_Styles or WP_Scripts
	 * @param  array $required_objects   List of registered script/style handles
	 * @param  string $type              Either 'styles' or 'scripts'
	 * @return void
	 */
	private function remove_conflicts( &$wp_objects, $required_objects, $type = 'scripts' ) {

        //allowing addons or other products to change the list of no conflict scripts or styles
        $required_objects = apply_filters( "gravityview_noconflict_{$type}", $required_objects );

        //reset queue
        $queue = array();
        foreach( $wp_objects->queue as $object ) {
            if( in_array( $object, $required_objects ) ) {
                $queue[] = $object;
            }
        }
        $wp_objects->queue = $queue;

        $required_objects = $this->add_script_dependencies( $wp_objects->registered, $required_objects );

        //unregistering scripts
        $registered = array();
        foreach( $wp_objects->registered as $handle => $script_registration ){
            if( in_array( $handle, $required_objects ) ){
                $registered[ $handle ] = $script_registration;
            }
        }
        $wp_objects->registered = $registered;
	}

	/**
	 * Add dependencies
	 * @todo  Move this to GravityView_Admin_Views
	 * @param [type] $registered [description]
	 * @param [type] $scripts    [description]
	 */
	private function add_script_dependencies($registered, $scripts){

        //gets all dependent scripts linked to the $scripts array passed
        do{
            $dependents = array();
            foreach($scripts as $script){
                $deps = isset($registered[$script]) && is_array($registered[$script]->deps) ? $registered[$script]->deps : array();
                foreach($deps as $dep){
                    if(!in_array($dep, $scripts) && !in_array($dep, $dependents)){
                        $dependents[] = $dep;
                    }
                }
            }
            $scripts = array_merge($scripts, $dependents);
        }while(!empty($dependents));

        return $scripts;
    }

    /** DEBUG */

    /**
     * Logs messages using Gravity Forms logging add-on
     * @param  string $message log message
     * @return void
     */
    public static function log_debug( $message ){
    	do_action( 'gravityview_log_debug', $message );
    }

    /**
     * Logs messages using Gravity Forms logging add-on
     * @param  string $message log message
     * @return void
     */
    public static function log_error( $message ){
    	do_action( 'gravityview_log_error', $message );
    }

} // end class GravityView_Plugin

add_action('plugins_loaded', array('GravityView_Plugin', 'getInstance'), 1);
