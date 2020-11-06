<?php

/**
 * @since 1.2
 */
class GravityView_Change_Entry_Creator {

	function __construct() {

		/**
		 * @since  1.5.1
		 */
		add_action( 'gform_user_registered', array( $this, 'assign_new_user_to_lead' ), 10, 4 );

		// ONLY ADMIN FROM HERE ON.
		if ( ! is_admin() ) {
			return;
		}

		/**
		 * @filter `gravityview_disable_change_entry_creator` Disable the Change Entry Creator functionality
		 * @since  1.7.4
		 * @param boolean $disable Disable the Change Entry Creator functionality. Default: false.
		 */
		if ( apply_filters( 'gravityview_disable_change_entry_creator', false ) ) {
			return;
		}

		/**
		 * Use `init` to fix bbPress warning
		 *
		 * @see https://bbpress.trac.wordpress.org/ticket/2309
		 */
		add_action( 'init', array( $this, 'load' ), 100 );

		add_action( 'plugins_loaded', array( $this, 'prevent_conflicts' ) );

		// Enqueues SelectWoo script and style.
		add_action( 'admin_enqueue_scripts', array( $this, 'add_selectwoo_assets' ) );

		// Ajax callback to get users to change entry creator.
		add_action( 'wp_ajax_entry_creator_get_users', array( $this, 'entry_creator_get_users' ) );

	}

	/**
	 * Enqueues SelectWoo script and style.
	 *
	 * @since  2.9.1
	 *
	 */
	function add_selectwoo_assets() {

		$version      = GravityView_Plugin::version;
		$script_debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( 'gravityview-selectWoo', plugins_url( 'assets/lib/selectWoo/selectWoo.full.min.js', GRAVITYVIEW_FILE ), array(), $version );
		wp_enqueue_style( 'gravityview-selectWoo', plugins_url( 'assets/lib/selectWoo/selectWoo.min.css', GRAVITYVIEW_FILE ), array(), $version );

		wp_enqueue_script( 'gravityview_entry_creator', plugins_url( 'assets/js/admin-entry-creator' . $script_debug . '.js', GRAVITYVIEW_FILE ), array( 'gravityview-selectWoo' ), $version );

		wp_localize_script(
			'gravityview_entry_creator',
			'GVEntryCreator',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'action'  => 'entry_creator_get_users',
			)
		);
	}

	/**
	 * Get users list for entry creator.
	 *
	 * @since  2.9.1
	 *
	 */
	function entry_creator_get_users() {

		$post_var = wp_parse_args(
			wp_unslash( $_POST ),
			array(
				'q'        => '',
				'gv_nonce' => '',
			)
		);

		if ( ! wp_verify_nonce( $post_var['gv_nonce'], 'gv_entry_creator' ) ) {
			die();
		}

		$search_string = $post_var['q'];

		if ( is_numeric( $search_string ) ) {
			$user_args = array(
				'search'         => $search_string . '*',
				'search_columns' => array( 'ID' ),
			);
		} else {
			$user_args = array(
				'search'         => '*' . $search_string . '*',
				'search_columns' => array( 'user_login', 'user_email', 'user_nicename', 'display_name' ),
			);
		}

		$users = GVCommon::get_users( 'change_entry_creator', $user_args );

		echo wp_send_json( $users, 200 );

		die();
	}

	/**
	 * When an user is created using the User Registration add-on, assign the entry to them
	 *
	 * @since  1.5.1
	 * @param int    $user_id  WordPress User ID
	 * @param array  $config   User registration feed configuration
	 * @param array  $entry    GF Entry array
	 * @param string $password User password
	 * @return void
	 * @uses   RGFormsModel::update_lead_property() Modify the entry `created_by` field
	 */
	function assign_new_user_to_lead( $user_id, $config, $entry = array(), $password = '' ) {

		/**
		 * Disable assigning the new user to the entry by returning false.
		 *
		 * @param int   $user_id WordPress User ID
		 * @param array $config  User registration feed configuration
		 * @param array $entry   GF Entry array
		 */
		$assign_to_lead = apply_filters( 'gravityview_assign_new_user_to_entry', true, $user_id, $config, $entry );

		// If filter returns false, do not process
		if ( empty( $assign_to_lead ) ) {
			return;
		}

		// Update the entry. The `false` prevents checking Akismet; `true` disables the user updated hook from firing
		$result = RGFormsModel::update_entry_property( (int) $entry['id'], 'created_by', (int) $user_id, false, true );

		if ( false === $result ) {
			$status = __( 'Error', 'gravityview' );
			global $wpdb;
			$note = sprintf( '%s: Failed to assign User ID #%d as the entry creator (Last database error: "%s")', $status, $user_id, $wpdb->last_error );
		} else {
			$status = __( 'Success', 'gravityview' );
			$note   = sprintf( _x( '%s: Assigned User ID #%d as the entry creator.', 'First parameter: Success or error of the action. Second: User ID number', 'gravityview' ), $status, $user_id );
		}

		gravityview()->log->debug( 'GravityView_Change_Entry_Creator[assign_new_user_to_lead] - {note}', array( 'note' => $note ) );

		/**
		 * @filter `gravityview_disable_change_entry_creator_note` Disable adding a note when changing the entry creator
		 * @since  1.21.5
		 * @param boolean $disable Disable the Change Entry Creator note. Default: false.
		 */
		if ( apply_filters( 'gravityview_disable_change_entry_creator_note', false ) ) {
			return;
		}

		GravityView_Entry_Notes::add_note( $entry['id'], - 1, 'GravityView', $note, 'gravityview' );

	}

	/**
	 * Disable previous functionality; use this one as the canonical.
	 *
	 * @return void
	 */
	function prevent_conflicts() {

		// Plugin that was provided here:
		// @link https://gravityview.co/support/documentation/201991205/
		remove_action( "gform_entry_info", 'gravityview_change_entry_creator_form', 10 );
		remove_action( "gform_after_update_entry", 'gravityview_update_entry_creator', 10 );

	}

	/**
	 * @since  3.6.3
	 * @return void
	 */
	function load() {

		// Does GF exist?
		if ( ! class_exists( 'GFCommon' ) ) {
			return;
		}

		// Can the user edit entries?
		if ( ! GVCommon::has_cap( array( 'gravityforms_edit_entries', 'gravityview_edit_entries' ) ) ) {
			return;
		}

		// If screen mode isn't set, then we're in the wrong place.
		if ( empty( $_REQUEST['screen_mode'] ) ) {
			return;
		}

		// Now, no validation is required in the methods; let's hook in.
		add_action( 'admin_init', array( &$this, 'set_screen_mode' ) );

		add_action( "gform_entry_info", array( &$this, 'add_select' ), 10, 2 );

		add_action( "gform_after_update_entry", array( &$this, 'update_entry_creator' ), 10, 2 );

	}

	/**
	 * Allows for edit links to work with a link instead of a form (GET instead of POST)
	 *
	 * @return void
	 */
	function set_screen_mode() {

		if ( 'view' === \GV\Utils::_POST( 'screen_mode' ) ) {
			return;
		}

		// If $_GET['screen_mode'] is set to edit, set $_POST value
		if ( \GV\Utils::_GET( 'screen_mode' ) === 'edit' ) {
			$_POST["screen_mode"] = 'edit';
		}

	}

	/**
	 * When the entry creator is changed, add a note to the entry
	 *
	 * @param array $form     GF entry array
	 * @param int   $entry_id Entry ID
	 * @return void
	 */
	function update_entry_creator( $form, $entry_id ) {

		global $current_user;

		// Update the entry
		$created_by = absint( \GV\Utils::_POST( 'created_by' ) );

		RGFormsModel::update_lead_property( $entry_id, 'created_by', $created_by );

		// If the creator has changed, let's add a note about who it used to be.
		$originally_created_by = \GV\Utils::_POST( 'originally_created_by' );

		// If there's no owner and there didn't used to be, keep going
		if ( empty( $originally_created_by ) && empty( $created_by ) ) {
			return;
		}

		// If the values have changed
		if ( absint( $originally_created_by ) !== absint( $created_by ) ) {

			$user_data = get_userdata( $current_user->ID );

			$user_format = _x( '%s (ID #%d)', 'The name and the ID of users who initiated changes to entry ownership', 'gravityview' );

			$original_name = $created_by_name = esc_attr_x( 'No User', 'To show that the entry was unassigned from an actual user to no user.', 'gravityview' );

			if ( ! empty( $originally_created_by ) ) {
				$originally_created_by_user_data = get_userdata( $originally_created_by );
				$original_name                   = sprintf( $user_format, $originally_created_by_user_data->display_name, $originally_created_by_user_data->ID );
			}

			if ( ! empty( $created_by ) ) {
				$created_by_user_data = get_userdata( $created_by );
				$created_by_name      = sprintf( $user_format, $created_by_user_data->display_name, $created_by_user_data->ID );
			}

			GravityView_Entry_Notes::add_note( $entry_id, $current_user->ID, $user_data->display_name, sprintf( __( 'Changed entry creator from %s to %s', 'gravityview' ), $original_name, $created_by_name ), 'note' );
		}

	}

	/**
	 * Output the select to change the entry creator
	 *
	 * @param int   $form_id GF Form ID
	 * @param array $entry   GF entry array
	 * @return void
	 */
	function add_select( $form_id, $entry ) {

		if ( \GV\Utils::_POST( 'screen_mode' ) !== 'edit' ) {
			return;
		}

		$output = '<label for="change_created_by">';
		$output .= esc_html__( 'Change Entry Creator:', 'gravityview' );
		$output .= '</label>';

		$output .= '<select name="created_by" id="change_created_by" class="widefat">';

		$created_by_id   = \GV\Utils::get( $entry, 'created_by' );
		$created_by_user = GVCommon::get_users( 'change_entry_creator', array( 'include' => $created_by_id ) );
		$created_by_user = isset( $created_by_user[0] ) ? $created_by_user[0] : array();

		if ( empty( $created_by_user ) ) {
			$output .= '<option value="0"> &mdash; ' . esc_attr_x( 'No User', 'No user assigned to the entry', 'gravityview' ) . ' &mdash; </option>';
		} else {
			$output .= '<option value="' . $created_by_user->ID . '" "selected">' . esc_attr( $created_by_user->display_name . ' (' . $created_by_user->user_nicename . ')' ) . '</option>';
		}

		$output .= '</select>';
		$output .= '<input name="originally_created_by" value="' . esc_attr( $entry['created_by'] ) . '" type="hidden" />';
		$output .= wp_nonce_field( 'gv_entry_creator', 'gv_entry_creator_nonce', false, false );

		echo $output;
	}

}

new GravityView_Change_Entry_Creator;
