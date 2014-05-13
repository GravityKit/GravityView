<?php
/**
 * The GravityView plugin
 *
 * Create directories based on a Gravity Forms form, insert them using a shortcode, and modify how they output.
 *
 * @package   GravityView
 * @license   GPL3+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @wordpress-plugin
 * Plugin Name:       GravityView
 * Plugin URI:        http://www.seodenver.com/
 * Description:       Create directories based on a Gravity Forms form, insert them using a shortcode, and modify how they output.
 * Version:           1.0.0
 * Author:            Katz Web Services, Inc.
 * Author URI:        http://www.katzwebservices.com
 * Text Domain:       gravity-view
 * License:           ToBeDefined
 * License URI:       ToBeDefined
 * Domain Path:       /languages
 * GitHub Plugin URI: ToBeDefined
 */

/** If this file is called directly, abort. */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/** Constants */
if ( !defined('GRAVITYVIEW_VERSION') )
	define( 'GRAVITYVIEW_VERSION', '1.0-beta' );
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

	const version = GRAVITYVIEW_VERSION;

	private $admin_notices = array();

	public function __construct() {

		require_once( GRAVITYVIEW_DIR . 'includes/class-settings.php');

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Load custom post types
		add_action( 'init', array( $this, 'init_setup' ) );

		// check if gravityforms is active
		add_action( 'admin_init', array( $this, 'check_gravityforms' ) );

		//throw notice messages if needed
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );


		if( is_admin() ) {

			// Filter Admin messages
			add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );

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

		// set the blacklist field types across the entire plugin
		add_filter( 'gravityview_blacklist_field_types', array( $this, 'default_field_blacklist' ), 10 );

		// adding general styles and scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts_and_styles') );

	    // Enable debug with Gravity Forms Logging Add-on
	    add_filter( 'gform_logging_supported', array( 'GravityView_Plugin', 'add_debug_settings' ) );

	}


	/**
	 * Check if Gravity Forms plugin is active
	 *
	 * @todo  Move this to GravityView_Admin_Views
	 * @access public
	 * @return void
	 */
	public function check_gravityforms() {

		$gf_status = self::get_plugin_status( 'gravityforms/gravityforms.php' );

		if( $gf_status !== true ) {

			if( $gf_status == 'inactive' ) {
				$this->admin_notices[] = array( 'class' => 'error', 'message' => sprintf( __( 'GravityView requires Gravity Forms to be active in order to run properly. %sActivate Gravity Forms%s to use the GravityView plugin.', 'gravity-view' ), '<strong><a href="'. wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=gravityforms/gravityforms.php' ), 'activate-plugin_gravityforms/gravityforms.php') . '">', '</a></strong>' ) );
			} else {
				$this->admin_notices[] = array( 'class' => 'error', 'message' => sprintf( __( 'GravityView requires Gravity Forms to be installed in order to run properly. %sGet Gravity Forms%s today', 'gravity-view' ), '</strong><a href="http://katz.si/gravityforms">' , '</a></strong>' ) );
			}

		}
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

		//Register Custom Post Type - gravityview
		$labels = array(
			'name'                => _x( 'Views', 'Post Type General Name', 'gravity-view' ),
			'singular_name'       => _x( 'View', 'Post Type Singular Name', 'gravity-view' ),
			'menu_name'           => __( 'Views', 'gravity-view' ),
			'parent_item_colon'   => __( 'Parent View:', 'gravity-view' ),
			'all_items'           => __( 'All Views', 'gravity-view' ),
			'view_item'           => __( 'View', 'gravity-view' ),
			'add_new_item'        => __( 'Add New View', 'gravity-view' ),
			'add_new'             => __( 'New View', 'gravity-view' ),
			'edit_item'           => __( 'Edit View', 'gravity-view' ),
			'update_item'         => __( 'Update View', 'gravity-view' ),
			'search_items'        => __( 'Search Views', 'gravity-view' ),
			'not_found'           => __( 'No Views found', 'gravity-view' ),
			'not_found_in_trash'  => __( 'No Views found in Trash', 'gravity-view' ),
		);
		$args = array(
			'label'               => __( 'view', 'gravity-view' ),
			'description'         => __( 'Create views based on a Gravity Forms form', 'gravity-view' ),
			'labels'              => $labels,
			'supports'            => array( 'title', ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => true,
			'menu_position'       => 15,
			'menu_icon'           => '',
			//'menu_icon'           => plugins_url('images/gravity-view-icon.png', GRAVITYVIEW_FILE),
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
	function post_updated_messages( $messages ) {
		global $post;

		$post_id = isset($_GET['post']) ? intval($_GET['post']) : NULL;

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
				date_i18n( __( 'M j, Y @ G:i', 'gravity-view' ), strtotime( $post->post_date ) )
			),
			10  => sprintf(__( 'View draft updated. %sView on website.%s', 'gravity-view' ), '<a href="'.get_permalink( $post_id ).'">', '</a>'),
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
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-welcome.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-views.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-add-shortcode.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-approve-entries.php' );
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

	/**
	 * List the field types without presentation properties (on a View context)
	 *
	 * @access public
	 * @return void
	 */
	function default_field_blacklist() {
		return array( 'html', 'section', 'captcha', 'page' );
	}


	/**
	 * Check if specified plugin is active, inactive or not installed
	 *
	 * @access public
	 * @static
	 * @param string $location (default: '')
	 * @return void
	 */
	static function get_plugin_status( $location = '' ) {

		if( ! function_exists('is_plugin_active') ) {
			include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if( is_plugin_active( $location ) ) {
			return true;
		}

		if( !file_exists( trailingslashit( WP_PLUGIN_DIR ) . $location ) ) {
			return false;
		}

		if( is_plugin_inactive( $location ) ) {
			return 'inactive';
		}
	}

	/**
	 * Enqueue global scripts and styles
	 *
	 * @access public
	 * @param mixed $hook
	 * @return void
	 */
	function add_scripts_and_styles() {

		wp_enqueue_style( 'gravityview_fonts', plugins_url('includes/css/admin-fonts.css', GRAVITYVIEW_FILE), array() );

	}




	/**
	 * Outputs the admin notices generated by the plugin
	 *
	 * @todo  Move this to GravityView_Admin_Views
	 * @return void
	 */
	function admin_notice() {

		if( empty( $this->admin_notices ) ) {
			return;
		}

		foreach( $this->admin_notices as $notice ) {

			echo '<div class="'. $notice['class'].'">';
			echo '<p>'. $notice['message'] .'</p>';
			echo '</div>';

		}
		//reset the notices handler
		$this->admin_notices = array();
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
		global $gravityview_settings;

		if( ! self::is_gravityview_page() || empty( $gravityview_settings['no-conflict-mode'] ) ) {
			return;
		}

		global $wp_styles;

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
     * Enables debug with Gravity Forms logging add-on
     * @param array $supported_plugins List of plugins
     */
    public static function add_debug_settings( $supported_plugins ) {
        $supported_plugins['gravityview'] = 'GravityView';
        return $supported_plugins;
    }

    /**
     * Logs messages using Gravity Forms logging add-on
     * @param  string $message log message
     * @return void
     */
    public static function log_debug( $message ){
        if ( class_exists("GFLogging") ) {
            GFLogging::include_logger();
            GFLogging::log_message( 'gravityview', $message, KLogger::DEBUG );
        }
    }

    /**
     * Logs messages using Gravity Forms logging add-on
     * @param  string $message log message
     * @return void
     */
    public static function log_error( $message ){
        if ( class_exists("GFLogging") ) {
            GFLogging::include_logger();
            GFLogging::log_message( 'gravityview', $message, KLogger::ERROR );
        }
    }

} // end class GravityView_Plugin

/** Launch plugin */
new GravityView_Plugin;
