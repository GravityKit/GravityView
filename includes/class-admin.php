<?php

class GravityView_Admin {

	static private $admin_notices = array();
	static private $dismissed_notices = array();

	function __construct() {

		if( !is_admin() ) { return; }

		$this->add_hooks();
	}

	/**
	 * @since 1.7.5
	 */
	function add_hooks() {

		add_action( 'network_admin_notices', array( $this, 'dismiss_notice' ), 50 );
		add_action( 'admin_notices', array( $this, 'dismiss_notice' ), 50 );
		add_action( 'admin_notices', array( $this, 'admin_notice' ), 100 );
		add_action( 'network_admin_notices', array( $this, 'admin_notice' ), 100 );

		// If Gravity Forms isn't active or compatibile, stop loading
		if( false === self::check_gravityforms() ) {
			return;
		}

		// Check if Gravity Forms Directory is running.
		self::add_default_notices();

		// Migrate Class
		require_once( GRAVITYVIEW_DIR . 'includes/class-migrate.php' );

		// Don't load tooltips if on Gravity Forms, otherwise it overrides translations
		if( !GFForms::is_gravity_page() ) {
			require_once( GFCommon::get_base_path() . '/tooltips.php' );
		}

		require_once( GRAVITYVIEW_DIR . 'includes/admin/metaboxes.php' );
		require_once( GRAVITYVIEW_DIR . 'includes/admin/entry-list.php' );
		require_once( GRAVITYVIEW_DIR . 'includes/class-change-entry-creator.php' );

		/** @since 1.6 */
		require_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-admin-duplicate-view.php' );

		// Filter Admin messages
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'post_updated_messages' ) );

		add_filter( 'plugin_action_links_'. plugin_basename( GRAVITYVIEW_FILE ) , array( $this, 'plugin_action_links' ) );

		add_action( 'plugins_loaded', array( $this, 'backend_actions' ), 100 );

		//Hooks for no-conflict functionality
		add_action( 'wp_print_scripts', array( $this, 'no_conflict_scripts' ), 1000);
		add_action( 'admin_print_footer_scripts', array( $this, 'no_conflict_scripts' ), 9);

		add_action( 'wp_print_styles', array( $this, 'no_conflict_styles' ), 1000);
		add_action( 'admin_print_styles', array( $this, 'no_conflict_styles' ), 11);
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

		include_once( GRAVITYVIEW_DIR .'includes/admin/class.field.type.php' );
		include_once( GRAVITYVIEW_DIR .'includes/admin/class.render.settings.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-label.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-views.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-welcome.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-add-shortcode.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-approve-entries.php' );

		include_once( GRAVITYVIEW_DIR .'includes/fields/class.field.php' );

		// Load Field files automatically
		foreach ( glob( GRAVITYVIEW_DIR . 'includes/fields/*.php' ) as $gv_field_filename ) {
			require_once( $gv_field_filename );
		}

		// Nice place to insert extensions' backend stuff
		do_action('gravityview_include_backend_actions');
	}

	/**
	 * Modify plugin action links at plugins screen
	 *
	 * @access public
	 * @static
	 * @param mixed $links
	 * @return array Action links with Support included
	 */
	public static function plugin_action_links( $links ) {

		$action = array( '<a href="http://docs.gravityview.co">'. esc_html__( 'Support', 'gravityview' ) .'</a>' );

		return array_merge( $action, $links );
	}

	/**
	 * Get an image of our intrepid explorer friend
	 * @return string HTML image tag with floaty's cute mug on it
	 */
	public static function get_floaty() {

		if( is_rtl() ) {
			$style = 'margin:10px 10px 10px 0;';
			$class = 'alignright';
		} else {
			$style = 'margin:10px 10px 10px 0;';
			$class = 'alignleft';
		}

		return '<img src="'.plugins_url( 'assets/images/astronaut-200x263.png', GRAVITYVIEW_FILE ).'" class="'.$class.'" height="87" width="66" alt="The GravityView Astronaut Says:" style="'.$style.'" />';
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
				$image = self::get_floaty();
				$new_form_text .= '<h3>'.$image.sprintf( __( 'A new form was created for this View: "%s"', 'gravityview' ), $form_name ).'</h3>';
				$new_form_text .=  sprintf( __( '%sThere are no entries for the new form, so the View will also be empty.%s To start collecting entries, you can add submissions through %sthe preview form%s and also embed the form on a post or page using this code: %s

					You can %sedit the form%s in Gravity Forms and the updated fields will be available here. Don&rsquo;t forget to %scustomize the form settings%s.
					', 'gravityview' ), '<strong>', '</strong>', '<a href="'.site_url( '?gf_page=preview&amp;id='.$connected_form ).'">', '</a>', '<code>[gravityform id="'.$connected_form.'" name="'.$form_name.'"]</code>', '<a href="'.admin_url( 'admin.php?page=gf_edit_forms&amp;id='.$connected_form ).'">', '</a>', '<a href="'.admin_url( 'admin.php?page=gf_edit_forms&amp;view=settings&amp;id='.$connected_form ).'">', '</a>');
				$new_form_text = wpautop( $new_form_text );

				delete_post_meta( $post_id, '_gravityview_start_fresh' );
			}
		}

		$messages['gravityview'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => sprintf(__( 'View updated. %sView on website.%s', 'gravityview' ), '<a href="'.get_permalink( $post_id ).'">', '</a>'),
			2  => sprintf(__( 'View updated. %sView on website.%s', 'gravityview' ), '<a href="'.get_permalink( $post_id ).'">', '</a>'),
			3  => __( 'View deleted.', 'gravityview' ),
			4  => sprintf(__( 'View updated. %sView on website.%s', 'gravityview' ), '<a href="'.get_permalink( $post_id ).'">', '</a>'),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'View restored to revision from %s', 'gravityview' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf(__( 'View published. %sView on website.%s', 'gravityview' ), '<a href="'.get_permalink( $post_id ).'">', '</a>') . $new_form_text,
			7  => sprintf(__( 'View saved. %sView on website.%s', 'gravityview' ), '<a href="'.get_permalink( $post_id ).'">', '</a>') . $new_form_text,
			8  => __( 'View submitted.', 'gravityview' ),
			9  => sprintf(
				__( 'View scheduled for: %1$s.', 'gravityview' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'gravityview' ), strtotime( ( isset( $post->post_date ) ? $post->post_date : NULL )  ) )
			) . $new_form_text,
			10  => sprintf(__( 'View draft updated. %sView on website.%s', 'gravityview' ), '<a href="'.get_permalink( $post_id ).'">', '</a>'),

			/**
			 * These apply to `bulk_post_updated_messages`
			 * @file wp-admin/edit.php
			 */
			'updated'   => _n( '%s View updated.', '%s Views updated.', $bulk_counts['updated'], 'gravityview' ),
			'locked'    => _n( '%s View not updated, somebody is editing it.', '%s Views not updated, somebody is editing them.', $bulk_counts['locked'], 'gravityview' ),
			'deleted'   => _n( '%s View permanently deleted.', '%s Views permanently deleted.', $bulk_counts['deleted'], 'gravityview' ),
			'trashed'   => _n( '%s View moved to the Trash.', '%s Views moved to the Trash.', $bulk_counts['trashed'], 'gravityview' ),
			'untrashed' => _n( '%s View restored from the Trash.', '%s Views restored from the Trash.', $bulk_counts['untrashed'], 'gravityview' ),
		);

		return $messages;
	}

	/**
	 * Callback to eliminate any non-registered script
	 * @return void
	 */
	function no_conflict_scripts() {
		global $wp_scripts;

		if( ! gravityview_is_admin_page() ) {
			return;
		}

		$no_conflict_mode = GravityView_Settings::getSetting('no-conflict-mode');

		if( empty( $no_conflict_mode ) ) {
			return;
		}


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
			'inline-edit-post',
            'utils',
            'svg-painter',
            'wp-auth-check',
            'heartbeat',
			'media-editor',
			'media-upload',
            'thickbox',
			'wp-color-picker',

            'gform_tooltip_init',
            'gform_field_filter',
            'gform_forms',

		    // Settings
			'gv-admin-edd-license',

            // Common
            'select2-js',
            'qtip-js',

            // jQuery
			'jquery',
            'jquery-ui-core',
            'jquery-ui-sortable',
            'jquery-ui-datepicker',
            'jquery-ui-dialog',
            'jquery-ui-slider',
			'jquery-ui-dialog',
			'jquery-ui-tabs',
			'jquery-ui-draggable',
			'jquery-ui-droppable',
            'jquery-ui-accordion',

			// WP SEO
			'wp-seo-metabox',
			'wpseo-admin-media',
			'jquery-qtip',
			'jquery-ui-autocomplete',
		);

		$this->remove_conflicts( $wp_scripts, $wp_required_scripts, 'scripts' );
	}

	/**
	 * Callback to eliminate any non-registered style
	 * @return void
	 */
	function no_conflict_styles() {
		global $wp_styles;

		if( ! gravityview_is_admin_page() ) {
			return;
		}

		// Dequeue other jQuery styles even if no-conflict is off.
		// Terrible-looking tabs help no one.
		if( !empty( $wp_styles->registered ) )  {
			foreach ($wp_styles->registered as $key => $style) {
				if( preg_match( '/^(?:wp\-)?jquery/ism', $key ) ) {
					wp_dequeue_style( $key );
				}
			}
		}

		$no_conflict_mode = GravityView_Settings::getSetting('no-conflict-mode');

		// If no conflict is off, jQuery will suffice.
		if( empty( $no_conflict_mode ) ) {
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

            // Settings
	        'gravityview_settings',

	        // WP SEO
	        'wp-seo-metabox',
	        'wpseo-admin-media',
	        'metabox-tabs',
	        'metabox-classic',
	        'metabox-fresh',

	        // @todo qTip styles not loading for some reason!
	        'jquery-qtip.js',
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
	        if( in_array( $object, $required_objects ) || preg_match('/gravityview|gf_|gravityforms/ism', $object ) ) {
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
	 *
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
	 * Dismiss a GravityView notice - stores the dismissed notices for 16 weeks
	 * @return void
	 */
    function dismiss_notice() {

    	// No dismiss sent
    	if( empty( $_GET['gv-dismiss'] ) ) {
    		return;
    	}

    	// Invalid nonce
    	if( !wp_verify_nonce( $_GET['gv-dismiss'], 'dismiss' ) ) {
    		return;
    	}

    	$notice_id = esc_attr( $_GET['notice'] );

    	//don't display a message if use has dismissed the message for this version
    	$dismissed_notices = (array)get_transient( 'gravityview_dismissed_notices' );

    	$dismissed_notices[] = $notice_id;

    	$dismissed_notices = array_unique( $dismissed_notices );

    	// Remind users every 16 weeks
    	set_transient( 'gravityview_dismissed_notices', $dismissed_notices, WEEK_IN_SECONDS * 16 );

    }

    /**
     * Should the notice be shown in the admin (Has it been dismissed already)?
     *
     * If the passed notice array has a `dismiss` key, the notice is dismissable. If it's dismissable,
     * we check against other notices that have already been dismissed.
     *
     * @see GravityView_Admin::dismiss_notice()
     * @see GravityView_Admin::add_notice()
     * @param  string $notice            Notice array, set using `add_notice()`.
     * @return boolean                   True: show notice; False: hide notice
     */
    function _maybe_show_notice( $notice ) {

	    // There are no dismissed notices.
    	if( empty( self::$dismissed_notices ) ) {
    		return true;
    	}

    	// Has the
    	$is_dismissed = !empty( $notice['dismiss'] ) && in_array( $notice['dismiss'], self::$dismissed_notices );

    	return $is_dismissed ? false : true;
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

		if( GravityView_Plugin::is_network_activated() && !is_main_site() ) {
			return;
		}

		//don't display a message if use has dismissed the message for this version
		self::$dismissed_notices = (array)get_transient( 'gravityview_dismissed_notices' );

		foreach( self::$admin_notices as $notice ) {

			if( false === $this->_maybe_show_notice( $notice ) ) {
				continue;
			}

			echo '<div id="message" class="'. esc_attr( $notice['class'] ).'">';

			// Too cute to leave out.
			echo GravityView_Admin::get_floaty() ;

			if( !empty( $notice['title'] ) ) {
				echo '<h3>'.esc_html( $notice['title'] ) .'</h3>';
			}

			echo wpautop( $notice['message'] );

			if( !empty( $notice['dismiss'] ) ) {

				$dismiss = esc_attr($notice['dismiss']);

				$url = esc_url( add_query_arg( array( 'gv-dismiss' => wp_create_nonce( 'dismiss' ), 'notice' => $dismiss ) ) );

				echo wpautop( '<a href="'.$url.'" data-notice="'.$dismiss.'" class="button-small button button-secondary">'.esc_html__('Dismiss', 'gravityview' ).'</a>' );
			}

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
	 * Check for potential conflicts and let users know about common issues.
	 *
	 * @return void
	 */
	public static function add_default_notices() {

		if( class_exists( 'GFDirectory' ) ) {
			self::$admin_notices['gf_directory'] = array(
				'class' => 'error',
				'title' => __('Potential Conflict', 'gravityview' ),
				'message' => __( 'GravityView and Gravity Forms Directory are both active. This may cause problems. If you experience issues, disable the Gravity Forms Directory plugin.', 'gravityview' ),
				'dismiss' => 'gf_directory',
			);
		}

		if( !class_exists('GF_Fields') ) {
			self::$admin_notices['gf_directory'] = array(
				'class' => 'error',
				'title' => __('GravityView will soon require Gravity Forms 1.9 or higher.', 'gravityview' ),
				'message' => esc_html__( 'You are using an older version of Gravity Forms. Please update Gravity Forms plugin to the latest version.', 'gravityview' ),
				'dismiss' => 'gf_version',
			);
		}

	}

	/**
	 * Check if Gravity Forms plugin is active and show notice if not.
	 *
	 * @access public
	 * @return boolean True: checks have been passed; GV is fine to run; False: checks have failed, don't continue loading
	 */
	public static function check_gravityforms() {

		// Bypass other checks: if the class exists
		if( class_exists( 'GFCommon' ) ) {

			// and the version's right, we're good.
			if( true === version_compare( GFCommon::$version, GV_MIN_GF_VERSION, ">=" ) ) {
				return true;
			}

			// Or the version's wrong
			self::$admin_notices['gf_version'] = array( 'class' => 'error', 'message' => sprintf( __( "%sGravityView requires Gravity Forms Version %s or newer.%s \n\nYou're using Version %s. Please update your Gravity Forms or purchase a license. %sGet Gravity Forms%s - starting at $39%s%s", 'gravityview' ), '<h3>', GV_MIN_GF_VERSION, "</h3>\n\n", '<tt>'.GFCommon::$version.'</tt>', "\n\n".'<a href="http://katz.si/gravityforms" class="button button-secondary button-large button-hero">' , '<em>', '</em>', '</a>') );

			return false;
		}

		$gf_status = self::get_plugin_status( 'gravityforms/gravityforms.php' );

		// If GFCommon doesn't exist, assume GF not active
		$return = false;

		switch( $gf_status ) {
			case 'inactive':
				$return = false;
				self::$admin_notices['gf_inactive'] = array( 'class' => 'error', 'message' => sprintf( __( '%sGravityView requires Gravity Forms to be active. %sActivate Gravity Forms%s to use the GravityView plugin.', 'gravityview' ), '<h3>', "</h3>\n\n".'<strong><a href="'. wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=gravityforms/gravityforms.php' ), 'activate-plugin_gravityforms/gravityforms.php') . '" class="button button-large">', '</a></strong>' ) );
				break;
			default:
				/**
				 * The plugin is activated and yet somehow GFCommon didn't get picked up...
				 */
				if( $gf_status === true ) {
					$return = true;
				} else {
					self::$admin_notices['gf_installed'] = array( 'class' => 'error', 'message' => sprintf( __( '%sGravityView requires Gravity Forms to be installed in order to run properly. %sGet Gravity Forms%s - starting at $39%s%s', 'gravityview' ), '<h3>', "</h3>\n\n".'<a href="http://katz.si/gravityforms" class="button button-secondary button-large button-hero">' , '<em>', '</em>', '</a>') );
				}
				break;
		}

		return $return;
	}

	/**
	 * Check if specified plugin is active, inactive or not installed
	 *
	 * @access public
	 * @static
	 * @param string $location (default: '')
	 * @return boolean|string True: plugin is active; False: plugin file doesn't exist at path; 'inactive' it's inactive
	 */
	static function get_plugin_status( $location = '' ) {

		if( ! function_exists('is_plugin_active') ) {
			include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if( is_plugin_active( $location ) ) {
			return true;
		}

		if(
			!file_exists( trailingslashit( WP_PLUGIN_DIR ) . $location ) &&
			!file_exists( trailingslashit( WPMU_PLUGIN_DIR ) . $location )
		) {
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
			} elseif ( $plugin_page === 'gravityview_settings' || ( !empty( $_GET['page'] ) && $_GET['page'] === 'gravityview_settings' ) ) {
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
