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

		require_once( GRAVITYVIEW_DIR . 'includes/admin/metaboxes/class-gravityview-admin-metaboxes.php' );
		require_once( GRAVITYVIEW_DIR . 'includes/admin/entry-list.php' );
		require_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-change-entry-creator.php' );

		/** @since 1.15 **/
		require_once( GRAVITYVIEW_DIR . 'includes/admin/class-gravityview-support-port.php' );

		/** @since 1.6 */
		require_once( GRAVITYVIEW_DIR . 'includes/class-gravityview-admin-duplicate-view.php' );

		/** @since 1.17 */
		require_once( GRAVITYVIEW_DIR . 'includes/admin/class-gravityview-admin-no-conflict.php' );
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

	}

	/**
	 * Get text for no views found.
	 *
	 * @since 1.18 Moved to GravityView_Admin
	 *
	 * @return string HTML message with no container tags.
	 */
	public static function no_views_text() {
		
		if ( isset( $_REQUEST['post_status'] ) && 'trash' === $_REQUEST['post_status'] ) {
			return __( 'No Views found in Trash', 'gravityview' );
		} elseif( ! empty( $_GET['s'] ) ) {
			return __( 'No Views found.', 'gravityview' );
		}

		// Floaty the Astronaut says "oi"
		$image = self::get_floaty();

		if ( GVCommon::has_cap( 'edit_gravityviews' ) ) {
			$output = sprintf( esc_attr__( "%sYou don't have any active views. Let&rsquo;s go %screate one%s!%s\n\nIf you feel like you're lost in space and need help getting started, check out the %sGetting Started%s page.", 'gravityview' ), '<h3>', '<a href="' . admin_url( 'post-new.php?post_type=gravityview' ) . '">', '</a>', '</h3>', '<a href="' . admin_url( 'edit.php?post_type=gravityview&page=gv-getting-started' ) . '">', '</a>' );
		} else {
			$output = esc_attr__( 'There are no active Views', 'gravityview' );
		}

		return $image . wpautop( $output );
	}

	/**
	 * Display error HTML in Edit View when the form is in the trash or no longer exists in Gravity Forms
	 *
	 * @since 1.19
	 *
	 * @param int $form_id Gravity Forms
	 *
	 * @return void
	 */
	public static function connected_form_warning( $form_id = 0 ) {
        global $pagenow;

		if ( ! is_int( $form_id ) || $pagenow === 'post-new.php' ) {
			return;
		}

		$form_info = GFFormsModel::get_form( $form_id, true );

		$error = '';
		if ( empty( $form_info ) ) {
			$error = esc_html__( 'The form connected to this View no longer exists.', 'gravityview' );
			$error .= ' ' . esc_html__( 'Select another form as the data source for this View.', 'gravityview' );
		} elseif ( $form_info->is_trash ) {
			$error = esc_html__( 'The connected form is in the trash.', 'gravityview' );
			$error .= ' ' . gravityview_get_link( admin_url( 'admin.php?page=gf_edit_forms&filter=trash' ), esc_html__( 'Restore the form from the trash', 'gravityview' ) );
			$error .= ' ' . esc_html__( 'or select another form.', 'gravityview' );
		}

		if( $error ) {
			?>
			<div class="wp-dialog notice-warning inline error wp-clearfix">
				<?php echo gravityview_get_floaty(); ?>
				<h3><?php echo $error; ?></h3>
			</div>
			<?php
		}
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
		include_once( GRAVITYVIEW_DIR .'includes/admin/class-gravityview-admin-view-item.php' );
		include_once( GRAVITYVIEW_DIR .'includes/admin/class-gravityview-admin-view-field.php' );
		include_once( GRAVITYVIEW_DIR .'includes/admin/class-gravityview-admin-view-widget.php' );
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

		$post_id = get_the_ID();

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
			/* translators: %s and %s are HTML tags linking to the View on the website */
			1  => sprintf(__( 'View updated. %sView on website.%s', 'gravityview' ), '<a href="'.get_permalink( $post_id ).'">', '</a>'),
			/* translators: %s and %s are HTML tags linking to the View on the website */
			2  => sprintf(__( 'View updated. %sView on website.%s', 'gravityview' ), '<a href="'.get_permalink( $post_id ).'">', '</a>'),
			3  => __( 'View deleted.', 'gravityview' ),
			/* translators: %s and %s are HTML tags linking to the View on the website */
			4  => sprintf(__( 'View updated. %sView on website.%s', 'gravityview' ), '<a href="'.get_permalink( $post_id ).'">', '</a>'),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'View restored to revision from %s', 'gravityview' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			/* translators: %s and %s are HTML tags linking to the View on the website */
			6  => sprintf(__( 'View published. %sView on website.%s', 'gravityview' ), '<a href="'.get_permalink( $post_id ).'">', '</a>') . $new_form_text,
			/* translators: %s and %s are HTML tags linking to the View on the website */
			7  => sprintf(__( 'View saved. %sView on website.%s', 'gravityview' ), '<a href="'.get_permalink( $post_id ).'">', '</a>') . $new_form_text,
			8  => __( 'View submitted.', 'gravityview' ),
			9  => sprintf(
		        /* translators: Date and time the View is scheduled to be published */
				__( 'View scheduled for: %1$s.', 'gravityview' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'gravityview' ), strtotime( ( isset( $post->post_date ) ? $post->post_date : NULL )  ) )
			) . $new_form_text,
			/* translators: %s and %s are HTML tags linking to the View on the website */
			10  => sprintf(__( 'View draft updated. %sView on website.%s', 'gravityview' ), '<a href="'.get_permalink( $post_id ).'">', '</a>') . $new_form_text,

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
	 * @return bool|string If `false`, not a GravityView page. `true` if $page is passed and is the same as current page. Otherwise, the name of the page (`single`, `settings`, or `views`)
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

/**
 * Alias for GravityView_Admin::is_admin_page()
 *
 * @see GravityView_Admin::is_admin_page
 *
 * @param string $hook
 * @param null|string $page Optional. String return value of page to compare against.
 *
 * @return bool|string If `false`, not a GravityView page. `true` if $page is passed and is the same as current page. Otherwise, the name of the page (`single`, `settings`, or `views`)
 */
function gravityview_is_admin_page($hook = '', $page = NULL) {
	return GravityView_Admin::is_admin_page( $hook, $page );
}
