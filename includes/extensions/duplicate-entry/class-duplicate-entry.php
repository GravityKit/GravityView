<?php
/**
 * The GravityView Duplicate Entry Extension
 *
 * Duplicate entries in GravityView.
 *
 * @since     2.5
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * @since 2.5
 */
final class GravityView_Duplicate_Entry {

	/**
	 * @var string The location of this file.
	 */
	static $file;

	/**
	 * @var GravityView_Duplicate_Entry This instance.
	 */
	static $instance;

	var $view_id;

	function __construct() {

		self::$file = plugin_dir_path( __FILE__ );
		$this->add_hooks();
	}

	/**
	 * @since 2.5
	 */
	private function add_hooks() {

		add_action( 'wp', array( $this, 'process_duplicate' ), 10000 );

		add_filter( 'gravityview_entry_default_fields', array( $this, 'add_default_field' ), 10, 3 );

		add_action( 'gravityview_before', array( $this, 'maybe_display_message' ) );

		// For the Duplicate Entry Link, you don't want visible to all users.
		add_filter( 'gravityview_field_visibility_caps', array( $this, 'modify_visibility_caps' ), 10, 5 );

		// Modify the field options based on the name of the field type
		add_filter( 'gravityview_template_duplicate_link_options', array( $this, 'duplicate_link_field_options' ), 10, 5 );

		// add template path to check for field
		add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );

		// Entry duplication in the backend
		add_action( 'gform_entries_first_column_actions', array( $this, 'make_duplicate_link_row' ), 10, 5 );

		// Handle duplicate action in the backend
		add_action( 'gform_pre_entry_list', array( $this, 'maybe_duplicate_list' ) );

		add_filter( 'gravityview/sortable/field_blocklist', array( $this, '_filter_sortable_fields' ), 1 );

		add_filter( 'gravityview/field/is_visible', array( $this, 'maybe_not_visible' ), 10, 3 );

		add_filter( 'gravityview/api/reserved_query_args', array( $this, 'add_reserved_arg' ) );
	}

	/**
	 * Adds "duplicate" to the list of internal reserved query args
	 *
	 * @since 2.10
	 *
	 * @param array $args Existing reserved args
	 *
	 * @return array
	 */
	public function add_reserved_arg( $args ) {

		$args[] = 'duplicate';

		return $args;
	}

	/**
	 * Return the instantiated class object
	 *
	 * @since  2.5
	 * @return GravityView_Duplicate_Entry
	 */
	public static function getInstance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hide the field or not.
	 *
	 * For non-logged in users.
	 * For users that have no duplicate rights on any of the current entries.
	 *
	 * @param bool      $visible Visible or not.
	 * @param \GV\Field $field The field.
	 * @param \GV\View  $view The View context.
	 *
	 * @return bool
	 */
	public function maybe_not_visible( $visible, $field, $view ) {

		if ( 'duplicate_link' !== $field->ID ) {
			return $visible;
		}

		if ( ! $view ) {
			return $visible;
		}

		static $visibility_cache_for_view = array();

		$anchor_id = $view->get_anchor_id();

		if ( ! is_null( $result = \GV\Utils::get( $visibility_cache_for_view, $anchor_id, null ) ) ) {
			return $result;
		}

		foreach ( $view->get_entries()->all() as $entry ) {
			if ( self::check_user_cap_duplicate_entry( $entry->as_entry(), $field->as_configuration() ) ) {
				// At least one entry is duplicable for this user
				$visibility_cache_for_view[ $anchor_id ] = true;
				return true;
			}
		}

		$visibility_cache_for_view[ $anchor_id ] = false;

		return false;
	}

	/**
	 * Prevent users from being able to sort by the Duplicate field
	 *
	 * @since 2.8.3
	 *
	 * @param array $fields Array of field types not editable by users
	 *
	 * @return array
	 */
	public function _filter_sortable_fields( $fields ) {

		$fields = (array) $fields;

		$fields[] = 'duplicate_link';

		return $fields;
	}

	/**
	 * Include this extension templates path
	 *
	 * @since  2.5
	 *
	 * @param array $file_paths List of template paths ordered
	 *
	 * @return array File paths, with duplicate field path added at index 117
	 */
	public function add_template_path( $file_paths ) {

		// Index 100 is the default GravityView template path.
		// Index 110 is Edit Entry link
		$file_paths[117] = self::$file;

		return $file_paths;
	}

	/**
	 * Add "Duplicate Link Text" setting to the edit_link field settings
	 *
	 * @since  2.5
	 *
	 * @param  array  $field_options [description]
	 * @param  [type] $template_id   [description]
	 * @param  [type] $field_id      [description]
	 * @param  [type] $context       [description]
	 * @param  [type] $input_type    [description]
	 *
	 * @return array                [description]
	 */
	public function duplicate_link_field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		// Always a link, never a filter, always same window
		unset( $field_options['show_as_link'], $field_options['search_filter'], $field_options['new_window'] );

		// Duplicate Entry link should only appear to visitors capable of editing entries
		unset( $field_options['only_loggedin'], $field_options['only_loggedin_cap'] );

		$add_option['duplicate_link'] = array(
			'type'       => 'text',
			'label'      => __( 'Duplicate Link Text', 'gk-gravityview' ),
			'desc'       => null,
			'value'      => __( 'Duplicate Entry', 'gk-gravityview' ),
			'merge_tags' => true,
		);

		$field_options['allow_duplicate_cap'] = array(
			'type'    => 'select',
			'label'   => __( 'Allow the following users to duplicate the entry:', 'gk-gravityview' ),
			'choices' => GravityView_Render_Settings::get_cap_choices( $template_id, $field_id, $context, $input_type ),
			'tooltip' => 'allow_duplicate_cap',
			'class'   => 'widefat',
			'value'   => 'read', // Default: entry creator
		);

		return array_merge( $add_option, $field_options );
	}


	/**
	 * Add Edit Link as a default field, outside those set in the Gravity Form form
	 *
	 * @since 2.5
	 *
	 * @param array        $entry_default_fields Existing fields
	 * @param  string|array $form form_ID or form object
	 * @param  string       $zone   Either 'single', 'directory', 'edit', 'header', 'footer'
	 *
	 * @return array $entry_default_fields, with `duplicate_link` added. Won't be added if in Edit Entry context.
	 */
	public function add_default_field( $entry_default_fields, $form = array(), $zone = '' ) {

		if ( 'edit' !== $zone ) {
			$entry_default_fields['duplicate_link'] = array(
				'label' => __( 'Duplicate Entry', 'gk-gravityview' ),
				'type'  => 'duplicate_link',
				'desc'  => __( 'A link to duplicate the entry. Respects the Duplicate Entry permissions.', 'gk-gravityview' ),
				'icon'  => 'dashicons-controls-repeat',
			);
		}

		return $entry_default_fields;
	}

	/**
	 * Add Duplicate Entry Link to the Add Field dialog
	 *
	 * @since 2.5
	 *
	 * @param array $available_fields
	 *
	 * @return array Fields with `duplicate_link` added
	 */
	public function add_available_field( $available_fields = array() ) {

		$available_fields['duplicate_link'] = array(
			'label_text'    => __( 'Duplicate Entry', 'gk-gravityview' ),
			'field_id'      => 'duplicate_link',
			'label_type'    => 'field',
			'input_type'    => 'duplicate_link',
			'field_options' => null,
		);

		return $available_fields;
	}

	/**
	 * Change wording for the Edit context to read Entry Creator
	 *
	 * @since 2.5
	 *
	 * @param  array        $visibility_caps        Array of capabilities to display in field dropdown.
	 * @param  string       $field_type  Type of field options to render (`field` or `widget`)
	 * @param  string       $template_id Table slug
	 * @param  float|string $field_id    GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
	 * @param  string       $context     What context are we in? Example: `single` or `directory`
	 * @param  string       $input_type  (textarea, list, select, etc.)
	 *
	 * @return array                   Array of field options with `label`, `value`, `type`, `default` keys
	 */
	public function modify_visibility_caps( $visibility_caps = array(), $template_id = '', $field_id = '', $context = '', $input_type = '' ) {

		$caps = $visibility_caps;

		// If we're configuring fields in the edit context, we want a limited selection
		if ( 'duplicate_link' === $field_id ) {

			// Remove other built-in caps.
			unset( $caps['publish_posts'], $caps['gravityforms_view_entries'], $caps['duplicate_others_posts'] );

			$caps['read'] = _x( 'Entry Creator', 'User capability', 'gk-gravityview' );
		}

		return $caps;
	}

	/**
	 * Generate a consistent nonce key based on the Entry ID
	 *
	 * @since 2.5
	 *
	 * @param  int $entry_id Entry ID
	 *
	 * @return string           Key used to validate request
	 */
	public static function get_nonce_key( $entry_id ) {
		return sprintf( 'duplicate_%s', $entry_id );
	}


	/**
	 * Generate a nonce link with the base URL of the current View embed
	 *
	 * We don't want to link to the single entry, because when duplicated, there would be nothing to return to.
	 *
	 * @since 2.5
	 *
	 * @param  array $entry Gravity Forms entry array
	 * @param  int   $view_id The View id. Not optional since 2.0
	 * @param  int   $post_id ID of the current post/page being embedded on, if any
	 *
	 * @return string|null If directory link is valid, the URL to process the duplicate request. Otherwise, `NULL`.
	 */
	public static function get_duplicate_link( $entry, $view_id, $post_id = null ) {

		$base = GravityView_API::directory_link( $post_id ? : $view_id, true );

		if ( empty( $base ) ) {
			gravityview()->log->error( 'Post ID does not exist: {post_id}', array( 'post_id' => $post_id ) );
			return null;
		}

		$actionurl = add_query_arg(
			array(
				'action'   => 'duplicate',
				'entry_id' => $entry['id'],
				'gvid'     => $view_id,
				'view_id'  => $view_id,
			),
			$base
		);

		return add_query_arg( 'duplicate', wp_create_nonce( self::get_nonce_key( $entry['id'] ) ), $actionurl );
	}

	/**
	 * Handle the duplication request, if $_GET['action'] is set to "duplicate"
	 *
	 * 1. Check referrer validity
	 * 2. Make sure there's an entry with the slug of $_GET['entry_id']
	 * 3. If so, attempt to duplicate the entry. If not, set the error status
	 * 4. Remove `action=duplicate` from the URL
	 * 5. Redirect to the page using `wp_safe_redirect()`
	 *
	 * @since 2.5
	 *
	 * @uses wp_safe_redirect()
	 *
	 * @return void|string $url URL during tests instead of redirect.
	 */
	public function process_duplicate() {

		// If the form is submitted
		if ( ( ! isset( $_GET['action'] ) ) || 'duplicate' !== $_GET['action'] || ( ! isset( $_GET['entry_id'] ) ) ) {
			return;
		}

		// Make sure it's a GravityView request
		if ( ! $this->verify_nonce() ) {
			gravityview()->log->debug( 'Duplicate entry not processed: nonce validation failed.' );
			return;
		}

		// Get the entry slug
		$entry_slug = esc_attr( $_GET['entry_id'] );

		// See if there's an entry there
		$entry = gravityview_get_entry( $entry_slug, true, false );

		if ( $entry ) {

			$has_permission = $this->user_can_duplicate_entry( $entry );

			if ( is_wp_error( $has_permission ) ) {

				$messages = array(
					'message' => urlencode( $has_permission->get_error_message() ),
					'status'  => 'error',
				);

			} else {

				// Duplicate the entry
				$duplicate_response = $this->duplicate_entry( $entry );

				if ( is_wp_error( $duplicate_response ) ) {

					$messages = array(
						'message' => urlencode( $duplicate_response->get_error_message() ),
						'status'  => 'error',
					);

					gravityview()->log->error(
						'Entry {entry_slug} cannot be duplicated: {error_code} {error_message}',
						array(
							'entry_slug'    => $entry_slug,
							'error_code'    => $duplicate_response->get_error_code(),
							'error_message' => $duplicate_response->get_error_message(),
						)
					);

				} else {

					$messages = array(
						'status' => $duplicate_response,
					);

				}
			}
		} else {

			gravityview()->log->error( 'Duplicate entry failed: there was no entry with the entry slug {entry_slug}', array( 'entry_slug' => $entry_slug ) );

			$messages = array(
				'message' => urlencode( __( 'The entry does not exist.', 'gk-gravityview' ) ),
				'status'  => 'error',
			);
		}

		$redirect_to_base = esc_url_raw( remove_query_arg( array( 'action', 'gvid', 'entry_id' ) ) );
		$redirect_to      = add_query_arg( $messages, $redirect_to_base );

		if ( defined( 'DOING_GRAVITYVIEW_TESTS' ) || ! apply_filters( 'wp_redirect', $redirect_to ) ) {
			return $redirect_to;
		}

		wp_safe_redirect( $redirect_to );

		exit();
	}

	/**
	 * Duplicate the entry.
	 *
	 * Done after all the checks in self::process_duplicate.
	 *
	 * @since 2.5
	 *
	 * @param array $entry The entry to be duplicated
	 *
	 * @return WP_Error|boolean
	 */
	private function duplicate_entry( $entry ) {

		if ( ! $entry_id = \GV\Utils::get( $entry, 'id' ) ) {
			return new WP_Error( 'gravityview-duplicate-entry-missing', __( 'The entry does not exist.', 'gk-gravityview' ) );
		}

		gravityview()->log->debug( 'Starting duplicate entry: {entry_id}', array( 'entry_id' => $entry_id ) );

		global $wpdb;

		$entry_table      = GFFormsModel::get_entry_table_name();
		$entry_meta_table = GFFormsModel::get_entry_meta_table_name();

		if ( ! $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $entry_table WHERE ID = %d", $entry_id ), ARRAY_A ) ) {
			return new WP_Error( 'gravityview-duplicate-entry-missing', __( 'The entry does not exist.', 'gk-gravityview' ) );
		}

		$form = GVCommon::get_form( $entry['form_id'] );

		$row['id']           = null;
		$row['date_created'] = date( 'Y-m-d H:i:s', time() );
		$row['date_updated'] = $row['date_created'];
		$row['is_starred']   = false;
		$row['is_read']      = false;
		$row['ip']           = rgars( $form, 'personalData/preventIP' ) ? '' : GFFormsModel::get_ip();
		$row['source_url']   = esc_url_raw( remove_query_arg( array( 'action', 'gvid', 'result', 'duplicate', 'entry_id' ) ) );
		$row['user_agent']   = \GV\Utils::_SERVER( 'HTTP_USER_AGENT' );
		$row['created_by']   = wp_get_current_user()->ID;

		/**
		 * Modify the new entry details before it's created.
		 *
		 * @since 2.5
		 * @param array $row The entry details
		 * @param array $entry The original entry
		 */
		$row = apply_filters( 'gravityview/entry/duplicate/details', $row, $entry );

		if ( ! $wpdb->insert( $entry_table, $row ) ) {
			return new WP_Error( 'gravityview-duplicate-entry-db-details', __( 'There was an error duplicating the entry.', 'gk-gravityview' ) );
		}

		$duplicated_id = $wpdb->insert_id;

		$meta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $entry_meta_table WHERE entry_id = %d", $entry_id ), ARRAY_A );

		$duplicate_meta = new WP_List_Util( $meta );

		// Keys that should be reset by default
		$reset_meta = array( 'is_approved', 'gravityview_unique_id', 'workflow_current_status_timestamp' );
		foreach ( $reset_meta as $meta_key ) {
			$duplicate_meta->filter( array( 'meta_key' => $meta_key ), 'NOT' );
		}

		$save_this_meta = array();
		foreach ( $duplicate_meta->get_output() as $m ) {
			$save_this_meta[] = array(
				'meta_key'   => $m['meta_key'],
				'meta_value' => $m['meta_value'],
				'item_index' => $m['item_index'],
			);
		}

		// Update the row ID for later usage
		$row['id'] = $duplicated_id;

		/**
		 * Modify the new entry meta details.
		 *
		 * @param array $save_this_meta The duplicate meta. Use/add meta_key, meta_value, item_index.
		 * @param array $row The duplicated entry
		 * @param array $entry The original entry
		 */
		$save_this_meta = apply_filters( 'gravityview/entry/duplicate/meta', $save_this_meta, $row, $entry );

		foreach ( $save_this_meta as $data ) {
			$data['form_id']  = $entry['form_id'];
			$data['entry_id'] = $duplicated_id;

			if ( ! $wpdb->insert( $entry_meta_table, $data ) ) {
				return new WP_Error( 'gravityview-duplicate-entry-db-meta', __( 'There was an error duplicating the entry.', 'gk-gravityview' ) );
			}
		}

		$duplicated_entry = \GFAPI::get_entry( $duplicated_id );

		do_action( 'gform_entry_created', $duplicated_entry, $form );

		$duplicate_response = 'duplicated';

		/**
		 * Triggered when an entry is duplicated.
		 *
		 * @since 2.5
		 * @param  array $duplicated_entry The duplicated entry
		 * @param  array $entry The original entry
		*/
		do_action( 'gravityview/duplicate-entry/duplicated', $duplicated_entry, $entry );

		gravityview()->log->debug( 'Duplicate response: {duplicate_response}', array( 'duplicate_response' => $duplicate_response ) );

		return $duplicate_response;
	}

	/**
	 * Is the current nonce valid for editing the entry?
	 *
	 * @since 2.5
	 *
	 * @return boolean
	 */
	public function verify_nonce() {

		// No duplicate entry request was made
		if ( empty( $_GET['entry_id'] ) || empty( $_GET['duplicate'] ) ) {
			return false;
		}

		$nonce_key = self::get_nonce_key( $_GET['entry_id'] );

		$valid = wp_verify_nonce( $_GET['duplicate'], $nonce_key );

		/**
		 * Override Duplicate Entry nonce validation. Return true to declare nonce valid.
		 *
		 * @since 2.5
		 * @see wp_verify_nonce()
		 * @param int|boolean $valid False if invalid; 1 or 2 when nonce was generated
		 * @param string $nonce_key Name of nonce action used in wp_verify_nonce. $_GET['duplicate'] holds the nonce value itself. Default: `duplicate_{entry_id}`
		 */
		$valid = apply_filters( 'gravityview/duplicate-entry/verify_nonce', $valid, $nonce_key );

		return $valid;
	}

	/**
	 * Get the onclick attribute for the confirm dialogs that warns users before they duplicate an entry
	 *
	 * @since 2.5
	 *
	 * @return string HTML `onclick` attribute
	 */
	public static function get_confirm_dialog() {

		$confirm = __( 'Are you sure you want to duplicate this entry?', 'gk-gravityview' );

		/**
		 * Modify the Duplicate Entry Javascript confirmation text (will be sanitized when output).
		 *
		 * @param string $confirm Default: "Are you sure you want to duplicate this entry?". If empty, disable confirmation dialog.
		 */
		$confirm = apply_filters( 'gravityview/duplicate-entry/confirm-text', $confirm );

		if ( empty( $confirm ) ) {
			return '';
		}

		return 'return window.confirm(\'' . esc_js( $confirm ) . '\');';
	}

	/**
	 * Check if the user can edit the entry
	 *
	 * - Is the nonce valid?
	 * - Does the user have the right caps for the entry
	 * - Is the entry in the trash?
	 *
	 * @since 2.5
	 *
	 * @param  array $entry Gravity Forms entry array
	 * @param  int   $view_id ID of the View being rendered
	 *
	 * @return boolean|WP_Error        True: can edit form. WP_Error: nope.
	 */
	private function user_can_duplicate_entry( $entry = array(), $view_id = null ) {

		$error = null;

		if ( ! $this->verify_nonce() ) {
			$error = __( 'The link to duplicate this entry is not valid; it may have expired.', 'gk-gravityview' );
		}

		if ( ! self::check_user_cap_duplicate_entry( $entry, array(), $view_id ) ) {
			$error = __( 'You do not have permission to duplicate this entry.', 'gk-gravityview' );
		}

		// No errors; everything's fine here!
		if ( empty( $error ) ) {
			return true;
		}

		gravityview()->log->error( '{error}', array( 'erorr' => $error ) );

		return new WP_Error( 'gravityview-duplicate-entry-permissions', $error );
	}


	/**
	 * checks if user has permissions to view the link or duplicate a specific entry
	 *
	 * @since 2.5
	 *
	 * @param  array $entry Gravity Forms entry array
	 * @param array $field Field settings (optional)
	 * @param int   $view_id Pass a View ID to check caps against. If not set, check against current View
	 *
	 * @return bool
	 */
	public static function check_user_cap_duplicate_entry( $entry, $field = array(), $view_id = 0 ) {
		$current_user = wp_get_current_user();

		$entry_id = isset( $entry['id'] ) ? $entry['id'] : null;

		// Or if they can duplicate any entries (as defined in Gravity Forms), we're good.
		if ( GVCommon::has_cap( array( 'gravityforms_edit_entries', 'gform_full_access', 'gravityview_full_access' ), $entry_id ) ) {

			gravityview()->log->debug( 'Current user has `gravityforms_edit_entries` capability.' );

			return true;
		}

		// If field options are passed, check if current user can view the link
		if ( ! empty( $field ) ) {

			// If capability is not defined, something is not right!
			if ( empty( $field['allow_duplicate_cap'] ) ) {

				gravityview()->log->error( 'Cannot read duplicate entry field caps', array( 'data' => $field ) );

				return false;
			}

			if ( GVCommon::has_cap( $field['allow_duplicate_cap'] ) ) {

				// Do not return true if cap is read, as we need to check if the current user created the entry
				if ( 'read' !== $field['allow_duplicate_cap'] ) {
					return true;
				}
			} else {

				gravityview()->log->debug( 'User {user_id} is not authorized to view duplicate entry link ', array( 'user_id' => $current_user->ID ) );

				return false;
			}
		}

		if ( ! isset( $entry['created_by'] ) ) {

			gravityview()->log->error( 'Cannot duplicate entry; entry `created_by` doesn\'t exist.' );

			return false;
		}

		// Only checks user_duplicate view option if view is already set
		if ( $view_id ) {

			if ( ! $view = \GV\View::by_id( $view_id ) ) {
				return false;
			}

			$user_duplicate = $view->settings->get( 'user_duplicate', false );

			if ( empty( $user_duplicate ) ) {

				gravityview()->log->debug( 'User Duplicate is disabled. Returning false.' );

				return false;
			}
		}

		// If the logged-in user is the same as the user who created the entry, we're good.
		if ( is_user_logged_in() && intval( $current_user->ID ) === intval( $entry['created_by'] ) ) {

			gravityview()->log->debug( 'User {user_id} created the entry.', array( 'user_id' => $current_user->ID ) );

			return true;
		}

		return false;
	}


	/**
	 * After processing duplicate entry, the user will be redirected to the referring View or embedded post/page. Display a message on redirection.
	 *
	 * If success, there will be `status` URL parameters `status=>success`
	 * If an error, there will be `status` and `message` URL parameters `status=>error&message=example`
	 *
	 * @since 2.5
	 *
	 * @param int $current_view_id The ID of the View being rendered
	 *
	 * @return void
	 */
	public function maybe_display_message( $current_view_id = 0 ) {
		if ( empty( $_GET['status'] ) || ! self::verify_nonce() ) {
			return;
		}

		// Entry wasn't duplicated from current View
		if ( isset( $_GET['view_id'] ) && ( intval( $_GET['view_id'] ) !== intval( $current_view_id ) ) ) {
			return;
		}

		$this->display_message();
	}

	public function display_message() {
		if ( empty( $_GET['status'] ) || empty( $_GET['duplicate'] ) ) {
			return;
		}

		$status           = esc_attr( $_GET['status'] );
		$message_from_url = \GV\Utils::_GET( 'message', '' );
		$message_from_url = rawurldecode( stripslashes_deep( $message_from_url ) );
		$class            = '';

		switch ( $status ) {
			case 'error':
				$class         = ' gv-error error';
				$error_message = __( 'There was an error duplicating the entry: %s', 'gk-gravityview' );
				$message       = sprintf( $error_message, $message_from_url );
				break;
			default:
				$message = __( 'The entry was successfully duplicated.', 'gk-gravityview' );
				break;
		}

		/**
		 * Modify the Duplicate Entry messages. Allows HTML; will not be further sanitized.
		 *
		 * @since 2.5
		 * @param string $message Message to be displayed, sanitized using esc_attr()
		 * @param string $status Message status (`error` or `success`)
		 * @param string $message_from_url The original error message, if any, without the "There was an error duplicating the entry:" prefix
		 */
		$message = apply_filters( 'gravityview/duplicate-entry/message', esc_attr( $message ), $status, $message_from_url );

		// DISPLAY ERROR/SUCCESS MESSAGE
		echo '<div class="gv-notice' . esc_attr( $class ) . '">' . $message . '</div>';
	}

	/**
	 * Add a Duplicate link to the row of actions on the entry list in the backend.
	 *
	 * @since 2.5.1
	 *
	 * @param int    $form_id The form ID.
	 * @param int    $field_id The field ID.
	 * @param string $value The value.
	 * @param array  $entry The entry.
	 * @param string $query_string The query.
	 *
	 * @return void
	 */
	public function make_duplicate_link_row( $form_id, $field_id, $value, $entry, $query_string ) {

		/**
		 * Disables the duplicate link on the backend.
		 *
		 * @param boolean $enable True by default. Enabled.
		 * @param int $form_id The form ID.
		 */
		if ( ! apply_filters( 'gravityview/duplicate/backend/enable', true, $form_id ) ) {
			return;
		}

		?>
		<span class="gv-duplicate">
			|
			<a href="<?php echo wp_nonce_url( add_query_arg( 'entry_id', $entry['id'] ), self::get_nonce_key( $entry['id'] ), 'duplicate' ); ?>"><?php esc_html_e( 'Duplicate', 'gk-gravityview' ); ?></a>
		</span>
		<?php
	}

	/**
	 * Perhaps duplicate this entry if the action has been corrected.
	 *
	 * @since 2.5.1
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return void
	 */
	public function maybe_duplicate_list( $form_id ) {

		if ( ! is_admin() ) {
			return;
		}

		if ( 'success' === \GV\Utils::_GET( 'result' ) ) {
			add_filter(
				'gform_admin_messages',
				function ( $messages ) {
					$messages = (array) $messages;

					$messages[] = esc_html__( 'Entry duplicated.', 'gk-gravityview' );
					return $messages;
				}
			);
		}

		if ( 'error' === \GV\Utils::_GET( 'result' ) ) {
			$check_logs_message = sprintf(
				' <a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=gf_settings&subview=gravityformslogging' ) ),
				esc_html_x( 'Check the GravityView logs for more information.', 'Error message links to logging page', 'gk-gravityview' )
			);

			add_filter(
				'gform_admin_error_messages',
				function ( $messages ) use ( $check_logs_message ) {
					$messages = (array) $messages;

					$messages[] = esc_html__( 'There was an error duplicating the entry.', 'gk-gravityview' ) . $check_logs_message;

					return $messages;
				}
			);
		}

		if ( ! $this->verify_nonce() ) {
			return;
		}

		$entry_id = (int) $_GET['entry_id'] ?? 0;

		if ( ! GVCommon::has_cap( array( 'gravityforms_edit_entries', 'gform_full_access', 'gravityview_full_access' ), $entry_id ) ) {
			return;
		}

		$entry = GFAPI::get_entry( $entry_id );

		if ( is_wp_error( $entry ) ) {
			$is_duplicated = $entry;
		} else {
			$is_duplicated = $this->duplicate_entry( $entry );
		}

		if ( is_wp_error( $is_duplicated ) ) {
			gravityview()->log->error(
				'Error duplicating {id}: {error}',
				array(
					'id'    => $entry_id,
					'error' => $is_duplicated->get_error_message(),
				)
			);
		}

		$return_url = remove_query_arg( 'duplicate' );
		$return_url = add_query_arg( 'result', is_wp_error( $is_duplicated ) ? 'error' : 'success', $return_url );

		echo '<script>window.location.href = ' . json_encode( $return_url ) . ';</script>';

		exit;
	}
} // end class

GravityView_Duplicate_Entry::getInstance();

