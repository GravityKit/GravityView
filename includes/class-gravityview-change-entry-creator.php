<?php

/**
 * @since 1.2
 */
class GravityView_Change_Entry_Creator {
	/**
	 * Number of users to show in the select element.
	 *
	 * @var int
	 */
	public const DEFAULT_NUMBER_OF_USERS = 100;

	/**
	 * Initializes the hooks.
	 */
	public function __construct() {

		/**
		 * @since  1.5.1
		 */
		add_action( 'gform_user_registered', array( $this, 'assign_new_user_to_lead' ), 10, 3 );

		/**
		 * Disable the Change Entry Creator functionality.
		 *
		 * @since  1.7.4
		 *
		 * @param boolean $disable Disable the Change Entry Creator functionality. Default: false.
		 */
		if ( apply_filters( 'gravityview_disable_change_entry_creator', false ) ) {
			return;
		}

		add_filter( 'gravityview_entry_default_fields', [ $this, 'register_edit_field' ], 10, 3 );
		add_filter( 'gravityview/edit_entry/form_fields', [ $this, 'register_created_by_input' ], 10, 3 );
		add_filter( 'gravityview_field_visibility_caps', [ $this, 'created_by_visibility_caps' ], 15, 3 );

		/**
		 * Use `init` to fix bbPress warning.
		 *
		 * @see https://bbpress.trac.wordpress.org/ticket/2309
		 */
		add_action( 'init', array( $this, 'load' ), 100 );

		add_action( 'plugins_loaded', array( $this, 'prevent_conflicts' ) );

		// Enqueue and allow selectWoo UI assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_selectwoo_assets' ) );
		add_filter( 'gform_noconflict_scripts', array( $this, 'register_gform_noconflict' ) );
		add_filter( 'gform_noconflict_styles', array( $this, 'register_gform_noconflict' ) );

		// Ajax callback to get users to change entry creator.
		add_action( 'wp_ajax_entry_creator_get_users', array( $this, 'entry_creator_get_users' ) );
	}

	/**
	 * Enqueue selectWoo script and style.
	 *
	 * @since  2.9.1
	 */
	public function enqueue_selectwoo_assets() {

		if ( ! class_exists( 'GFForms' ) ) {
			return;
		}

		if ( GFForms::get_page() !== 'entry_detail_edit' ) {
			return;
		}

		$version      = \GV\Plugin::$version;
		$script_debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		if ( gravityview()->plugin->is_GF_25() ) {
			wp_deregister_script( 'gform_selectwoo' );
			wp_dequeue_script( 'gform_selectwoo' );
		}

		wp_enqueue_script( 'gravityview_selectwoo', plugins_url( 'assets/lib/selectWoo/selectWoo.full.min.js', GRAVITYVIEW_FILE ), array(), $version );
		wp_enqueue_style( 'gravityview_selectwoo', plugins_url( 'assets/lib/selectWoo/selectWoo.min.css', GRAVITYVIEW_FILE ), array(), $version );

		wp_enqueue_script(
			'gravityview_entry_creator',
			plugins_url( 'assets/js/admin-entry-creator' . $script_debug . '.js', GRAVITYVIEW_FILE ),
			[ 'jquery', 'gravityview_selectwoo' ],
			$version
		);

		wp_localize_script(
			'gravityview_entry_creator',
			'GVEntryCreator',
			array(
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => 'entry_creator_get_users',
				'gf25'     => (bool) gravityview()->plugin->is_GF_25(),
				'language' => array(
					'search_placeholder' => esc_html__( 'Search by ID, login, email, or name.', 'gk-gravityview' ),
				),
			)
		);
	}

	/**
	 * Get users list for entry creator.
	 *
	 * @since  2.9.1
	 */
	public function entry_creator_get_users() {

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

		wp_send_json( $users, 200 );
	}

	/**
	 * When a user is created using the User Registration add-on, assign the entry to them.
	 *
	 * @since  1.5.1
	 *
	 * @param int   $user_id WordPress User ID.
	 * @param array $config  User registration feed configuration.
	 * @param array $entry   GF Entry array.
	 *
	 * @return void
	 * @uses   RGFormsModel::update_lead_property() Modify the entry `created_by` field.
	 */
	public function assign_new_user_to_lead( $user_id, $config, $entry = array() ) {

		/**
		 * Disable assigning the new user to the entry by returning false.
		 *
		 * @param int   $user_id WordPress User ID
		 * @param array $config  User registration feed configuration
		 * @param array $entry   GF Entry array
		 */
		$assign_to_lead = apply_filters( 'gravityview_assign_new_user_to_entry', true, $user_id, $config, $entry );

		// If filter returns false, do not process.
		if ( empty( $assign_to_lead ) ) {
			return;
		}

		// Update the entry. The `false` prevents checking Akismet; `true` disables the user updated hook from firing.
		$result = RGFormsModel::update_entry_property( (int) $entry['id'], 'created_by', (int) $user_id, false, true );

		if ( false === $result ) {
			$status = __( 'Error', 'gk-gravityview' );
			global $wpdb;
			$note = sprintf( '%s: Failed to assign User ID #%d as the entry creator (Last database error: "%s")', $status, $user_id, $wpdb->last_error );
		} else {
			$status = __( 'Success', 'gk-gravityview' );
			// Translators: %1$s contains either `Success` or `error`, and %2$d contains the User ID.
			$note = sprintf( _x( '%1$s: Assigned User ID #%2$d as the entry creator.', 'First parameter: Success or error of the action. Second: User ID number', 'gk-gravityview' ), $status, $user_id );
		}

		gravityview()->log->debug( 'GravityView_Change_Entry_Creator[assign_new_user_to_lead] - {note}', array( 'note' => $note ) );

		/**
		 * Disable adding a note when changing the entry creator.
		 *
		 * @since  1.21.5
		 *
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
	public function prevent_conflicts() {
		/**
		 * Plugin that was provided here:
		 *
		 * @link https://www.gravitykit.com/support/documentation/201991205/
		 */
		remove_action( 'gform_entry_info', 'gravityview_change_entry_creator_form', 10 );
		remove_action( 'gform_after_update_entry', 'gravityview_update_entry_creator', 10 );
	}

	/**
	 * Whether the current user has the rights to edit the entry creator.
	 *
	 * @since 2.30.0
	 *
	 * @return bool Whether the user has rights.
	 */
	private function is_user_allowed(): bool {
		if ( ! GVCommon::has_cap( 'list_users' ) ) {
			return false;
		}

		// Can the user edit entries?
		if ( ! GVCommon::has_cap(
			[
				'gravityforms_edit_entries',
				'gravityview_edit_entries',
				'gravityview_edit_others_entries',
				'gravityview_edit_form_entries',
			]
		) ) {
			return false;
		}

		return true;
	}

	/**
	 * @since  3.6.3
	 * @return void
	 */
	public function load() {

		// Does GF exist?
		if ( ! class_exists( 'GFCommon' ) ) {
			return;
		}

		// Can the user edit entries?
		if ( ! $this->is_user_allowed() ) {
			return;
		}

		/**
		 * If screen mode isn't set, then we're in the wrong place.
		 * But if we posted a valid nonce, then we are legit.
		 */
		if (
			empty( $_REQUEST['screen_mode'] )
			&& (
				! rgpost( 'gv_entry_creator_nonce' )
				|| ! wp_verify_nonce( rgpost( 'gv_entry_creator_nonce' ), 'gv_entry_creator' )
			)
		) {
			return;
		}

		// Now, no validation is required in the methods; let's hook in.
		add_action( 'admin_init', [ $this, 'set_screen_mode' ] );
		add_action( 'gform_entry_info', [ $this, 'add_select' ], 10, 2 );
		add_action( 'gform_after_update_entry', [ $this, 'update_entry_creator' ], 10, 3 );
	}

	/**
	 * Allows for edit links to work with a link instead of a form (GET instead of POST)
	 *
	 * @return void
	 */
	public function set_screen_mode() {

		if ( 'view' === \GV\Utils::_POST( 'screen_mode' ) ) {
			return;
		}

		// If $_GET['screen_mode'] is set to edit, set $_POST value.
		if ( 'edit' === \GV\Utils::_GET( 'screen_mode' ) ) {
			$_POST['screen_mode'] = 'edit';
		}
	}

	/**
	 * When the entry creator is changed, add a note to the entry.
	 *
	 * @param array $form           GF entry array.
	 * @param int   $entry_id       Entry ID.
	 * @param array $original_entry The entry before updating.
	 *
	 * @return void
	 */
	public function update_entry_creator( $form, $entry_id, array $original_entry ) {

		global $current_user;

		// Update the entry.
		$created_by = absint( \GV\Utils::_POST( 'created_by' ) );

		RGFormsModel::update_lead_property( $entry_id, 'created_by', $created_by );

		// If the creator has changed, let's add a note about who it used to be.
		$originally_created_by = rgar( $original_entry, 'created_by' );

		// If there's no owner and there didn't used to be, keep going.
		if ( empty( $originally_created_by ) && empty( $created_by ) ) {
			return;
		}

		// If the values have changed.
		if ( absint( $originally_created_by ) !== absint( $created_by ) ) {

			$user_data = get_userdata( $current_user->ID );

			// Translators: %1$s contains the user's name, and %2$d contains the user ID.
			$user_format = _x( '%1$s (ID #%2$d)', 'The name and the ID of users who initiated changes to entry ownership', 'gk-gravityview' );

			$created_by_name = esc_attr_x( 'No User', 'To show that the entry was unassigned from an actual user to no user.', 'gk-gravityview' );
			$original_name   = $created_by_name;

			if ( ! empty( $originally_created_by ) ) {
				$originally_created_by_user_data = get_userdata( $originally_created_by );

				$original_name = ! empty( $originally_created_by_user_data ) ?
					sprintf( $user_format, $originally_created_by_user_data->display_name, $originally_created_by_user_data->ID ) :
					esc_attr_x( 'Deleted User', 'To show that the entry was created by a no longer existing user.', 'gk-gravityview' );
			}

			if ( ! empty( $created_by ) ) {
				$created_by_user_data = get_userdata( $created_by );

				$created_by_name = ! empty( $created_by_user_data ) ?
					sprintf( $user_format, $created_by_user_data->display_name, $created_by_user_data->ID ) :
					esc_attr_x( 'Deleted User', 'To show that the entry was created by a no longer existing user.', 'gk-gravityview' );
			}

			// Translators: %1$s contains the original user's name, %2$s contains the new user's name.
			GravityView_Entry_Notes::add_note( $entry_id, $current_user->ID, $user_data->display_name, sprintf( __( 'Changed entry creator from %1$s to %2$s', 'gk-gravityview' ), $original_name, $created_by_name ), 'note' );
		}
	}

	/**
	 * Returns the HTML for the user select field.
	 *
	 * @since 2.30.0
	 *
	 * @param array $entry The entry object.
	 *
	 * @return string The HTML.
	 */
	public static function get_select_field( array $entry ): string {
		$output = '<select name="created_by" id="change_created_by" class="widefat">';

		$entry_creator_user_id = \GV\Utils::get( $entry, 'created_by' );

		$entry_creator_user = GVCommon::get_users( 'change_entry_creator', array( 'include' => $entry_creator_user_id ) );
		$entry_creator_user = isset( $entry_creator_user[0] ) ? $entry_creator_user[0] : array();

		$output .= '<option value="0" ' . selected( true, empty( $entry_creator_user_id ), false ) . '> &mdash; ' . esc_attr_x( 'No User', 'No user assigned to the entry', 'gk-gravityview' ) . ' &mdash; </option>';

		// Always show the entry creator, even when the user isn't included within the pagination limits.
		if ( ! empty( $entry_creator_user_id ) && ! empty( $entry_creator_user ) ) {
			$output .= '<option value="' . $entry_creator_user->ID . '" selected="selected">' . esc_attr( $entry_creator_user->display_name . ' (' . $entry_creator_user->user_nicename . ')' ) . '</option>';
		}

		$all_users = GVCommon::get_users( 'change_entry_creator', array( 'number' => self::DEFAULT_NUMBER_OF_USERS ) );
		foreach ( $all_users as $user ) {
			if ( $entry_creator_user_id === $user->ID ) {
				continue;
			}

			$output .= '<option value="' . esc_attr( $user->ID ) . '">' . esc_attr( $user->display_name . ' (' . $user->user_nicename . ')' ) . '</option>';
		}

		$user_count      = count_users();
		$user_count      = $user_count['total_users'];
		$users_displayed = self::DEFAULT_NUMBER_OF_USERS + ( ! empty( $entry_creator_user ) ? 1 : 0 );
		if ( $user_count > $users_displayed ) {
			$remaining_users = $user_count - $users_displayed;
			$user_users      = _n( 'user', 'users', $remaining_users, 'gk-gravityview' );
			// Translators: %1$d is the user count, %2$s is either `user` or `users` (singular vs. plural).
			$message = esc_html_x( 'Use the input above to search the remaining %1$d %2$s.', '%d is replaced with user count %s is replaced with "user" or "users"', 'gk-gravityview' );
			$message = sprintf( $message, $remaining_users, $user_users );
			$output .= '<option value="_user_count" disabled="disabled">' . esc_html( $message ) . '</option>';
		}

		$output .= '</select>';
		$output .= wp_nonce_field( 'gv_entry_creator', 'gv_entry_creator_nonce', false, false );

		return $output;
	}

	/**
	 * Output select element used to change the entry creator.
	 *
	 * @param int   $form_id GF Form ID.
	 * @param array $entry   GF entry array.
	 *
	 * @return void
	 */
	public function add_select( $form_id, $entry ) {

		if ( 'edit' !== \GV\Utils::_POST( 'screen_mode' ) ) {
			return;
		}

		$output  = '<label for="change_created_by">';
		$output .= esc_html__( 'Change Entry Creator:', 'gk-gravityview' );
		$output .= '</label>';
		$output .= self::get_select_field( $entry );

		echo wp_kses(
			$output,
			[
				'label'  => [ 'for' => true ],
				'select' => [
					'id'    => true,
					'name'  => true,
					'class' => true,
				],
				'option' => [
					'value'    => true,
					'selected' => true,
					'disabled' => true,
				],
			]
		);
	}

	/**
	 * Allow UI assets.
	 *
	 * @param string[] $assets The asset urls.
	 *
	 * @return array Updated asset urls.
	 */
	public function register_gform_noconflict( $assets ) {
		$assets[] = 'gravityview_selectwoo';
		$assets[] = 'gravityview_entry_creator';

		return $assets;
	}

	/**
	 * Registers the `created_by` field on the `Edit Entry` tab.
	 *
	 * @since 2.30.0
	 *
	 * @param GF_Field[] $fields The registered fields.
	 * @param array      $form   The form object.
	 * @param string     $zone   The fields zone.
	 *
	 * @return array The updated fields array.
	 */
	public function register_edit_field( array $fields, array $form, string $zone ): array {
		if ( 'edit' !== $zone ) {
			return $fields;
		}

		$meta_fields = GravityView_Fields::get_all( array( 'meta', 'gravityview' ), $zone );
		$field       = $meta_fields['created_by'] ?? null;

		if ( $field ) {
			$fields += $field->as_array();
		}

		return $fields;
	}

	/**
	 * Registers the `created_by` field on the `Edit Entry` tab.
	 *
	 * @since 2.30.0
	 *
	 * @param GF_Field[] $fields          The registered fields.
	 * @param array|null $editable_fields The fields zone.
	 * @param array      $form            The form object.
	 *
	 * @return array The updated fields array.
	 */
	public function register_created_by_input( array $fields, ?array $editable_fields, array $form ): array {
		require_once GFCommon::get_base_path() . '/export.php';

		// Don't add the `created_by` field if the user can't change it.
		$editable_field_ids = array_flip(
			array_map(
				static function ( array $field ): string {
					return $field['id'] ?? 0;
				},
				$editable_fields ?? []
			)
		);

		$form        = GFExport::add_default_export_fields( $form );
		$form_fields = array_column( $form['fields'], null, 'id' );

		// Don't show field automatically, only when actively added.
		if ( null === $editable_fields || ! isset( $editable_field_ids['created_by'] ) ) {
			return $fields;
		}

		$configuration = $editable_fields[ $editable_field_ids['created_by'] ] ?? [];

		if ( ! GVCommon::has_cap( array( $configuration['allow_edit_cap'] ?? 'manage_options' ) ) ) {
			return $fields;
		}

		$fields[] = GravityView_Edit_Entry_Render::merge_field_properties( $form_fields['created_by'], $configuration );

		// Sort fields according to Gravity View.
		$sort_order_lookup = array_flip( array_keys( $editable_field_ids ) );

		uasort(
			$fields,
			static function ( GF_Field $a, GF_Field $b ) use ( $sort_order_lookup ): int {
				return $sort_order_lookup[ $a->id ] ?? 0 <=> $sort_order_lookup[ $b->id ];
			}
		);

		return $fields;
	}

	/**
	 * Manages the visibility capabilities for the `created_by` field on the edit page.
	 *
	 * @since 2.30.0
	 *
	 * @param array  $caps     The capabilities.
	 * @param string $template The template name.
	 * @param string $field    The field name.
	 *
	 * @return array The new capabilities.
	 */
	public function created_by_visibility_caps( array $caps, string $template, string $field ): array {
		if ( 'created_by' !== $field || false === strpos( $template, 'edit' ) ) {
			return $caps;
		}

		// Read users can't update the `created_by` field.
		unset( $caps['read'] );

		return $caps;
	}
}

new GravityView_Change_Entry_Creator();
