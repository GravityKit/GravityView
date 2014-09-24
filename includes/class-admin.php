<?php

class GravityView_Admin {

	static private $admin_notices = array();

	function __construct() {

		if( !is_admin() ) { return; }


		// If Gravity Forms isn't active or compatibile, stop loading
		if( false === self::check_gravityforms() ) {

			add_action( 'admin_notices', array( $this, 'admin_notice' ), 100 );

			return;
		}

		require_once( GFCommon::get_base_path() . '/tooltips.php' );

		require_once( GRAVITYVIEW_DIR . 'includes/admin/metaboxes.php' );
		require_once( GRAVITYVIEW_DIR . 'includes/admin/entry-list.php' );

		// Filter Admin messages
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'post_updated_messages' ) );

		add_filter( 'plugin_action_links_'. plugin_basename( GRAVITYVIEW_FILE ) , array( $this, 'plugin_action_links' ) );

		add_action( 'plugins_loaded', array( $this, 'backend_actions' ), 100 );

		//Hooks for no-conflict functionality
	    add_action( 'wp_print_scripts', array( $this, 'no_conflict_scripts' ), 1000);
	    add_action( 'admin_print_footer_scripts', array( $this, 'no_conflict_scripts' ), 9);

	    add_action( 'wp_print_styles', array( $this, 'no_conflict_styles' ), 1000);
	    add_action( 'admin_print_styles', array( $this, 'no_conflict_styles' ), 1);
	    add_action( 'admin_print_footer_scripts', array( $this, 'no_conflict_styles' ), 1);
	    add_action( 'admin_footer', array( $this, 'no_conflict_styles' ), 1);
	}

	/**
	 * Function to launch admin objects
	 *
	 * @access public
	 * @return void
	 */
	public function backend_actions() {

		include_once( GRAVITYVIEW_DIR .'includes/class-admin-label.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-views.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-welcome.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-add-shortcode.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-approve-entries.php' );

		include_once( GRAVITYVIEW_DIR .'includes/fields/class.field.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/entry-link.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/created-by.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/date.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/website.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/email.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/html.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/section.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/time.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/entry-date.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/address.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/fileupload.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/source-url.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/post-title.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/post-content.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/post-category.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/post-tags.php' );
		include_once( GRAVITYVIEW_DIR .'includes/fields/post-image.php' );

		// Nice place to insert extensions' backend stuff
		do_action('gravityview_include_backend_actions');
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

		$action = array( '<a href="https://gravityview.co/support/documentation/">'. esc_html__( 'Support', 'gravity-view' ) .'</a>' );

		return array_merge( $action, $links );
	}

	/**
	 * Filter Admin messages
	 *
	 * @param  array      $messages Existing messages
	 * @return array                Messages with GravityView views!
	 */
	function post_updated_messages( $messages, $bulk_counts = NULL ) {
		global $post;

		$post_id = isset($_GET['post']) ? intval($_GET['post']) : ( is_object( $post ) && isset( $post->ID ) ? $post->ID : NULL );

		// By default, there will only be one item being modified.
		// When in the `bulk_post_updated_messages` filter, there will be passed a number
		// of modified items that will override this array.
		$bulk_counts = is_null( $bulk_counts ) ? array( 'updated' => 1 , 'locked' => 1 , 'deleted' => 1 , 'trashed' => 1, 'untrashed' => 1 ) : $bulk_counts;


		// If we're starting fresh, a new form was created.
		// We should let the user know this is the case.
		$start_fresh = get_post_meta( $post_id, '_gravityview_start_fresh', true );

		$new_form_text = '';

		if( !empty( $start_fresh ) ) {

			// Get the form that was created
			$connected_form = gravityview_get_form_id( $post_id );

			if( !empty( $connected_form ) ) {
				$form = gravityview_get_form( $connected_form );
				$form_name = esc_attr( $form['title'] );
				$image = '<img src="'.plugins_url( 'images/astronaut-200x263.png', GRAVITYVIEW_FILE ).'" class="alignleft" height="87" width="66" alt="The GravityView Astronaut Says:" style="margin:0 1em 1.6em 0;" />';
				$new_form_text .= '<h3>'.$image.sprintf( __( 'A new form was created for this View: "%s"', 'gravity-view' ), $form_name ).'</h3>';
				$new_form_text .=  sprintf( __( '%sThere are no entries for the new form, so the View will also be empty.%s To start collecting entries, you can add submissions through %sthe preview form%s and also embed the form on a post or page using this code: %s

					You can %sedit the form%s in Gravity Forms and the updated fields will be available here. Don&rsquo;t forget to %scustomize the form settings%s.
					', 'gravity-view' ), '<strong>', '</strong>', '<a href="'.site_url( '?gf_page=preview&amp;id='.$connected_form ).'">', '</a>', '<code>[gravityform id="'.$connected_form.'" name="'.$form_name.'"]</code>', '<a href="'.admin_url( 'admin.php?page=gf_edit_forms&amp;id='.$connected_form ).'">', '</a>', '<a href="'.admin_url( 'admin.php?page=gf_edit_forms&amp;view=settings&amp;id='.$connected_form ).'">', '</a>');
				$new_form_text = wpautop( $new_form_text );

				delete_post_meta( $post_id, '_gravityview_start_fresh' );
			}
		}

		$messages['gravityview'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => sprintf(__( 'View updated. %sView on website.%s', 'gravity-view' ), '<a href="'.get_permalink( $post_id ).'">', '</a>'),
			2  => sprintf(__( 'View updated. %sView on website.%s', 'gravity-view' ), '<a href="'.get_permalink( $post_id ).'">', '</a>'),
			3  => __( 'View deleted.', 'gravity-view' ),
			4  => sprintf(__( 'View updated. %sView on website.%s', 'gravity-view' ), '<a href="'.get_permalink( $post_id ).'">', '</a>'),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'View restored to revision from %s', 'gravity-view' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf(__( 'View published. %sView on website.%s', 'gravity-view' ), '<a href="'.get_permalink( $post_id ).'">', '</a>') . $new_form_text,
			7  => sprintf(__( 'View saved. %sView on website.%s', 'gravity-view' ), '<a href="'.get_permalink( $post_id ).'">', '</a>') . $new_form_text,
			8  => __( 'View submitted.', 'gravity-view' ),
			9  => sprintf(
				__( 'View scheduled for: %1$s.', 'gravity-view' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'gravity-view' ), strtotime( ( isset( $post->post_date ) ? $post->post_date : NULL )  ) )
			) . $new_form_text,
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

            'gform_tooltip_init',
            'gform_field_filter',
            'gform_forms',

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
            'redux-edd_license',
            'redux-field-edd-js',
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

	        // Gravity Forms
	        'gform_tooltip',
	        'gform_font_awesome',

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
	        'redux-field-edd-css',
	        'redux-field-info-css',
	        'redux-edd_license',
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

		if( empty( self::$admin_notices ) ) {
			return;
		}

		foreach( self::$admin_notices as $notice ) {

			echo '<div id="message" class="'. esc_attr( $notice['class'] ).'">';
			echo wpautop( $notice['message'] );
			echo '<div class="clear"></div>';
			echo '</div>';

		}
		//reset the notices handler
		self::$admin_notices = array();
	}

	/**
	 * Add a notice to be displayed in the admin.
	 * @param array $notice Array with `class` and `message` keys. The message is not escaped.
	 */
	public static function add_notice( $notice = array() ) {

		if( !isset( $notice['message'] ) ) {
			do_action( 'gravityview_log_error', 'GravityView_Admin[add_notice] Notice not set', $notice );
			return;
		}

		$notice['class'] = empty( $notice['class'] ) ? 'error' : $notice['class'];

		self::$admin_notices[] = $notice;
	}

	/**
	 * Check if Gravity Forms plugin is active and show notice if not.
	 *
	 * @access public
	 * @return void
	 */
	public static function check_gravityforms() {

		// Bypass other checks: if the class exists and the version's right, we're good.
		if( class_exists( 'GFCommon' ) && true === version_compare( GFCommon::$version, GV_MIN_GF_VERSION, ">=" ) ) {
			return true;
		}

		$image = '<img src="'.plugins_url( 'images/astronaut-200x263.png', GRAVITYVIEW_FILE ).'" class="alignleft gv-astronaut" height="87" width="66" alt="The GravityView Astronaut Says:" style="margin: 0 10px 10px 0;" />';

		$gf_status = self::get_plugin_status( 'gravityforms/gravityforms.php' );

		if( $gf_status !== true ) {
			if( $gf_status === 'inactive' ) {
				self::$admin_notices['gf_inactive'] = array( 'class' => 'error', 'message' => sprintf( __( '%sGravityView requires Gravity Forms to be active. %sActivate Gravity Forms%s to use the GravityView plugin.', 'gravity-view' ), '<h3>'.$image, "</h3>\n\n".'<strong><a href="'. wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=gravityforms/gravityforms.php' ), 'activate-plugin_gravityforms/gravityforms.php') . '" class="button button-large">', '</a></strong>' ) );
			} else {
				self::$admin_notices['gf_installed'] = array( 'class' => 'error', 'message' => sprintf( __( '%sGravityView requires Gravity Forms to be installed in order to run properly. %sGet Gravity Forms%s - starting at $39%s%s', 'gravity-view' ), '<h3>'.$image, "</h3>\n\n".'<a href="http://katz.si/gravityforms" class="button button-secondary button-large button-hero">' , '<em>', '</em>', '</a>') );
			}
			return false;

		} else if( class_exists( 'GFCommon' ) && false === version_compare( GFCommon::$version, GV_MIN_GF_VERSION, ">=" ) ) {

			self::$admin_notices['gf_version'] = array( 'class' => 'error', 'message' => sprintf( __( "%sGravityView requires Gravity Forms Version 1.8 or newer.%s \n\nYou're using Version %s. Please update your Gravity Forms or purchase a license. %sGet Gravity Forms%s - starting at $39%s%s", 'gravity-view' ), '<h3>'.$image, "</h3>\n\n", '<tt>'.GFCommon::$version.'</tt>', "\n\n".'<a href="http://katz.si/gravityforms" class="button button-secondary button-large button-hero">' , '<em>', '</em>', '</a>') );

			return false;
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
		global $current_screen, $plugin_page, $pagenow, $post;

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
