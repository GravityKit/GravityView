<?php

class GravityView_Admin {

	private $admin_notices = array();

	function __construct() {

		// check if gravityforms is active
		add_action( 'admin_init', array( &$this, 'check_gravityforms') );

		if( is_admin() ) {

		// Enable Gravity Forms tooltips
			require_once( GFCommon::get_base_path() . '/tooltips.php' );

			require_once( GRAVITYVIEW_DIR . 'includes/admin/metaboxes.php' );

			//throw notice messages if needed
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );

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
		}
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
		include_once( GRAVITYVIEW_DIR .'includes/fields/entry-link.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/created-by.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/date.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/entry-date.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/fileupload.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/post-title.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/post-content.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/post-category.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/post-tags.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/post-image.php' );

		// Nice place to insert extensions' backend stuff
		do_action('gravityview_include_backend_actions');
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
	 * Callback to eliminate any non-registered script
	 * @return void
	 */
	function no_conflict_scripts() {

		global $gravityview_settings;

		if( ! gravityview_is_admin_page() || empty( $gravityview_settings['no-conflict-mode'] ) ) {
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

		if( ! gravityview_is_admin_page() ) {
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

	/**
	 * Outputs the admin notices generated by the plugin
	 *
	 * @return void
	 */
	function admin_notice() {

		if( empty( $this->admin_notices ) ) {
			return;
		}

		foreach( $this->admin_notices as $notice ) {

			echo '<div id="message" class="'. esc_attr( $notice['class'] ).'">';
			echo wpautop($notice['message']);
			echo '<div class="clear"></div>';
			echo '</div>';

		}
		//reset the notices handler
		$this->admin_notices = array();
	}

	/**
	 * Check if Gravity Forms plugin is active and show notice if not.
	 *
	 * @access public
	 * @return void
	 */
	function check_gravityforms() {

		$image = '<img src="'.plugins_url( 'images/astronaut-200x263.png', GRAVITYVIEW_FILE ).'" class="alignleft gv-astronaut" height="87" width="66" alt="The GravityView Astronaut Says:" style="margin: 0 10px 10px 0;" />';

		$gf_status = self::get_plugin_status( 'gravityforms/gravityforms.php' );

		if( $gf_status !== true ) {
			if( $gf_status === 'inactive' ) {
				$this->admin_notices[] = array( 'class' => 'error below-h2', 'message' => sprintf( __( '%sGravityView requires Gravity Forms to be active. %sActivate Gravity Forms%s to use the GravityView plugin.', 'gravity-view' ), '<h3>'.$image, "</h3>\n\n".'<strong><a href="'. wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=gravityforms/gravityforms.php' ), 'activate-plugin_gravityforms/gravityforms.php') . '" class="button button-large">', '</a></strong>' ) );
			} else {
				$this->admin_notices[] = array( 'class' => 'error', 'message' => sprintf( __( '%sGravityView requires Gravity Forms to be installed in order to run properly. %sGet Gravity Forms%s - starting at $39%s%s', 'gravity-view' ), '<h3>'.$image, "</h3>\n\n".'<a href="http://katz.si/gravityforms" class="button button-secondary button-large button-hero">' , '<em>', '</em>', '</a>') );
			}
			return false;

		} else if( class_exists( 'GFCommon' ) && false === version_compare( GFCommon::$version, GV_MIN_GF_VERSION, ">=" ) ) {

			$this->admin_notices[] = array( 'class' => 'error below-h2', 'message' => sprintf( __( "%sGravityView requires Gravity Forms Version 1.8 or newer.%s \n\nYou're using Version %s. Please update your Gravity Forms or purchase a license. %sGet Gravity Forms%s - starting at $39%s%s", 'gravity-view' ), '<h3>'.$image, "</h3>\n\n", '<tt>'.GFCommon::$version.'</tt>', "\n\n".'<a href="http://katz.si/gravityforms" class="button button-secondary button-large button-hero">' , '<em>', '</em>', '</a>') );

		}

		return true;
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

	static function is_admin_page($hook = '', $page = NULL) {
		global $current_filter, $current_screen, $plugin_page, $pagenow, $post, $wp_post_types;

		if( !is_admin() ) { return false; }

		$is_page = false;

		$is_gv_screen = (!empty($current_screen) && isset($current_screen->post_type) && $current_screen->post_type === 'gravityview');

		$is_gv_post_type_get = (isset($_GET['post_type']) && $_GET['post_type'] === 'gravityview');

		if( empty( $post ) && $pagenow === 'post.php' && !empty( $_GET['post'] ) ) {
			$gv_post = get_post( intval( $_GET['post'] ) );
			$is_gv_post_type = (!empty($gv_post) && !empty($gv_post->post_type) && $gv_post->post_type === 'gravityview');
		} else {
			$is_gv_post_type = (!empty($post) && !empty($post->post_type) && $post->post_type === 'gravityview');
		}

		if( $is_gv_screen || $is_gv_post_type || $is_gv_post_type || $is_gv_post_type_get ) {

			// $_GET `post_type` variable
			if(in_array($pagenow, array( 'post.php' , 'post-new.php' )) ) {
				$is_page = 'single';
			} elseif ($plugin_page === 'settings') {
				$is_page = 'settings';
			} else {
				$is_page = 'views';
			}
		}

		$is_page = apply_filters( 'gravityview_is_admin_page', $is_page, $hook );

		// If the current page is the same as the compared page
		if(!empty($page)) {
			return $is_page === $page;
		}

		return $is_page;
	}

}

new GravityView_Admin;

function gravityview_is_admin_page($hook = '', $page = NULL) {
	return GravityView_Admin::is_admin_page( $hook, $page );
}
