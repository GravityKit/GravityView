<?php

class GravityView_Admin {

	function __construct() {

		if( ! is_admin() ) { return; }

		// If Gravity Forms isn't active or compatibile, stop loading
		if( false === GravityView_Compatibility::is_valid() ) {
			return;
		}

		$this->include_required_files();
		$this->add_hooks();
	}

	/**
	 * @since 1.15
	 * @return void
	 */
	private function include_required_files() {

		// Migrate Class
		require_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-migrate.php' );

		// Don't load tooltips if on Gravity Forms, otherwise it overrides translations
		if( class_exists( 'GFCommon' ) && class_exists( 'GFForms' ) && !GFForms::is_gravity_page() ) {
			require_once( GFCommon::get_base_path() . '/tooltips.php' );
		}

		require_once( GRAVITYVIEW_DIR . 'includes/admin/metaboxes/class-gravityview-admin-metaboxes.php' );
		require_once( GRAVITYVIEW_DIR . 'includes/admin/entry-list.php' );
		require_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-change-entry-creator.php' );

		/** @since 1.15 **/
		require_once( GRAVITYVIEW_DIR . 'includes/admin/class-gravityview-support-port.php' );

		/** @since 1.6 */
		require_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-admin-duplicate-view.php' );
	}

	/**
	 * @since 1.7.5
	 * @return void
	 */
	private function add_hooks() {

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

		/** @define "GRAVITYVIEW_DIR" "../" */
		include_once( GRAVITYVIEW_DIR .'includes/admin/class.field.type.php' );
		include_once( GRAVITYVIEW_DIR .'includes/admin/class.render.settings.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-label.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-views.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-welcome.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-add-shortcode.php' );
		include_once( GRAVITYVIEW_DIR .'includes/class-admin-approve-entries.php' );

		/**
		 * @action `gravityview_include_backend_actions` Triggered after all GravityView admin files are loaded
		 *
		 * Nice place to insert extensions' backend stuff
		 */
		do_action('gravityview_include_backend_actions');
	}

	/**
	 * Modify plugin action links at plugins screen
	 *
	 * @since 1.15 Added check for `gravityview_view_settings` and `gravityview_support_port` capabilities
	 * @access public
	 * @static
	 * @param array $links Array of action links under GravityView on the plugin page
	 * @return array Action links with Settings and Support included, if the user has the appropriate caps
	 */
	public static function plugin_action_links( $links ) {

		$actions = array();

		if( GVCommon::has_cap( 'gravityview_view_settings' ) ) {
			$actions[] = sprintf( '<a href="%s">%s</a>', admin_url( 'edit.php?post_type=gravityview&page=gravityview_settings' ), esc_html__( 'Settings', 'gravityview' ) );
		}

		if( GVCommon::has_cap( 'gravityview_support_port' ) ) {
			$actions[] = '<a href="http://docs.gravityview.co">' . esc_html__( 'Support', 'gravityview' ) . '</a>';
		}

		return array_merge( $actions, $links );
	}

	/**
	 * Get an image of our intrepid explorer friend
	 * @return string HTML image tag with floaty's cute mug on it
	 */
	public static function get_floaty() {
		return gravityview_get_floaty();
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

		$wp_allowed_scripts = array(
            'common',
            'admin-bar',
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
		);

		$this->remove_conflicts( $wp_scripts, $wp_allowed_scripts, 'scripts' );
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

        $wp_allowed_styles = array(
	        'admin-bar',
        	'colors',
	        'ie',
	        'wp-auth-check',
	        'media-views',
			'thickbox',
			'dashicons',
	        'wp-jquery-ui-dialog',
	        'jquery-ui-sortable',

            // Settings
	        'gravityview_settings',

	        // @todo qTip styles not loading for some reason!
	        'jquery-qtip.js',
	    );

		$this->remove_conflicts( $wp_styles, $wp_allowed_styles, 'styles' );

		/**
		 * @action `gravityview_remove_conflicts_after` Runs after no-conflict styles are removed. You can re-add styles here.
		 */
		do_action('gravityview_remove_conflicts_after');
	}

	/**
	 * Remove any style or script non-registered in the no conflict mode
	 * @todo  Move this to GravityView_Admin_Views
	 * @param  WP_Dependencies $wp_objects        Object of WP_Styles or WP_Scripts
	 * @param  array $required_objects   List of registered script/style handles
	 * @param  string $type              Either 'styles' or 'scripts'
	 * @return void
	 */
	private function remove_conflicts( &$wp_objects, $required_objects, $type = 'scripts' ) {

        /**
         * @filter `gravityview_noconflict_{$type}` Modify the list of no conflict scripts or styles\n
         * Filter is `gravityview_noconflict_scripts` or `gravityview_noconflict_styles`
         * @param array $required_objects
         */
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
	private function add_script_dependencies($registered, $scripts) {

		//gets all dependent scripts linked to the $scripts array passed
		do {
			$dependents = array();
			foreach ( $scripts as $script ) {
				$deps = isset( $registered[ $script ] ) && is_array( $registered[ $script ]->deps ) ? $registered[ $script ]->deps : array();
				foreach ( $deps as $dep ) {
					if ( ! in_array( $dep, $scripts ) && ! in_array( $dep, $dependents ) ) {
						$dependents[] = $dep;
					}
				}
			}
			$scripts = array_merge( $scripts, $dependents );
		} while ( ! empty( $dependents ) );

		return $scripts;
	}


	/**
	 * Get admin notices
	 * @deprecated since 1.12
	 * @return array
	 */
	public static function get_notices() {
		return GravityView_Admin_Notices::get_notices();
	}

	/**
	 * Add a notice to be displayed in the admin.
	 * @deprecated since 1.12
	 * @param array $notice Array with `class` and `message` keys. The message is not escaped.
	 */
	public static function add_notice( $notice = array() ) {
		GravityView_Admin_Notices::add_notice( $notice );
	}

	/**
	 * Check if Gravity Forms plugin is active and show notice if not.
	 *
	 * @deprecated since 1.12
	 * @see GravityView_Compatibility::get_plugin_status()
	 * @return boolean True: checks have been passed; GV is fine to run; False: checks have failed, don't continue loading
	 */
	public static function check_gravityforms() {
		return GravityView_Compatibility::check_gravityforms();
	}

	/**
	 * Check if specified plugin is active, inactive or not installed
	 *
	 * @deprecated since 1.12
	 * @see GravityView_Compatibility::get_plugin_status()

	 * @return boolean|string True: plugin is active; False: plugin file doesn't exist at path; 'inactive' it's inactive
	 */
	static function get_plugin_status( $location = '' ) {
		return GravityView_Compatibility::get_plugin_status( $location );
	}

	/**
	 * Is the current admin page a GravityView-related page?
	 *
	 * @todo Convert to use WP_Screen
	 * @param string $hook
	 * @param null|string $page Optional. String return value of page to compare against.
	 *
	 * @return bool|string|void If `false`, not a GravityView page. `true` if $page is passed and is the same as current page. Otherwise, the name of the page (`single`, `settings`, or `views`)
	 */
	static function is_admin_page( $hook = '', $page = NULL ) {
		global $current_screen, $plugin_page, $pagenow, $post;

		if( ! is_admin() ) { return false; }

		$is_page = false;

		$is_gv_screen = (!empty($current_screen) && isset($current_screen->post_type) && $current_screen->post_type === 'gravityview');

		$is_gv_post_type_get = (isset($_GET['post_type']) && $_GET['post_type'] === 'gravityview');

		$is_gv_settings_get = isset( $_GET['page'] ) && $_GET['page'] === 'gravityview_settings';

		if( empty( $post ) && $pagenow === 'post.php' && !empty( $_GET['post'] ) ) {
			$gv_post = get_post( intval( $_GET['post'] ) );
			$is_gv_post_type = (!empty($gv_post) && !empty($gv_post->post_type) && $gv_post->post_type === 'gravityview');
		} else {
			$is_gv_post_type = (!empty($post) && !empty($post->post_type) && $post->post_type === 'gravityview');
		}

		if( $is_gv_screen || $is_gv_post_type || $is_gv_post_type || $is_gv_post_type_get || $is_gv_settings_get ) {

			// $_GET `post_type` variable
			if(in_array($pagenow, array( 'post.php' , 'post-new.php' )) ) {
				$is_page = 'single';
			} else if ( in_array( $plugin_page, array( 'gravityview_settings', 'gravityview_page_gravityview_settings' ) ) || ( !empty( $_GET['page'] ) && $_GET['page'] === 'gravityview_settings' ) ) {
				$is_page = 'settings';
			} else {
				$is_page = 'views';
			}
		}

		/**
		 * @filter `gravityview_is_admin_page` Is the current admin page a GravityView-related page?
		 * @param[in,out] string|bool $is_page If false, no. If string, the name of the page (`single`, `settings`, or `views`)
		 * @param[in] string $hook The name of the page to check against. Is passed to the method.
		 */
		$is_page = apply_filters( 'gravityview_is_admin_page', $is_page, $hook );

		// If the current page is the same as the compared page
		if( !empty( $page ) ) {
			return $is_page === $page;
		}

		return $is_page;
	}

}

new GravityView_Admin;

function gravityview_is_admin_page($hook = '', $page = NULL) {
	return GravityView_Admin::is_admin_page( $hook, $page );
}
