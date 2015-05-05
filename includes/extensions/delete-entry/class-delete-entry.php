<?php
/**
 * The GravityView Delete Entry Extension
 *
 * Delete entries in GravityView.
 *
 * @since     1.5.1
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * @since 1.5.1
 */
final class GravityView_Delete_Entry {

	static $file;
	static $instance;
	var $entry;
	var $form;
	var $view_id;
	var $is_valid = NULL;

	function __construct() {

		self::$instance = &$this;

		self::$file = plugin_dir_path( __FILE__ );

		add_action( 'wp', array( $this, 'process_delete' ), 10000 );

		add_filter( 'gravityview_entry_default_fields', array( $this, 'add_default_field'), 10, 3 );

		add_action( 'gravityview_before', array( $this, 'display_message' ) );

		// For the Delete Entry Link, you don't want visible to all users.
		add_filter( 'gravityview_field_visibility_caps', array( $this, 'modify_visibility_caps'), 10, 5 );

		// Modify the field options based on the name of the field type
		add_filter( 'gravityview_template_delete_link_options', array( $this, 'delete_link_field_options' ), 10, 5 );

		// add template path to check for field
		add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );

		add_action( 'gravityview/edit-entry/publishing-action/after', array( $this, 'add_delete_button'), 10, 3 );

	}

	/**
	 * Return the instantiated class object
	 *
	 * @since  1.5.1
	 * @return GravityView_Delete_Entry
	 */
	static function getInstance() {

		if( empty( self::$instance ) ) {
			self::$instance = new GravityView_Delete_Entry;
		}

		return self::$instance;
	}

	/**
	 * Include this extension templates path
	 *
	 * @since  1.5.1
	 * @param array $file_paths List of template paths ordered
	 */
	function add_template_path( $file_paths ) {

		// Index 100 is the default GravityView template path.
		// Index 110 is Edit Entry link
		$file_paths[ 115 ] = self::$file;

		return $file_paths;
	}

	/**
	 * Add "Delete Link Text" setting to the edit_link field settings
	 *
	 * @since  1.5.1
	 * @param  [type] $field_options [description]
	 * @param  [type] $template_id   [description]
	 * @param  [type] $field_id      [description]
	 * @param  [type] $context       [description]
	 * @param  [type] $input_type    [description]
	 * @return [type]                [description]
	 */
	function delete_link_field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		// Always a link, never a filter
		unset( $field_options['show_as_link'], $field_options['search_filter'] );

		// Delete Entry link should only appear to visitors capable of editing entries
		unset( $field_options['only_loggedin'], $field_options['only_loggedin_cap'] );

		$add_option['delete_link'] = array(
			'type' => 'text',
			'label' => __( 'Delete Link Text', 'gravityview' ),
			'desc' => NULL,
			'value' => __('Delete Entry', 'gravityview'),
			'merge_tags' => true,
		);

		$field_options['allow_edit_cap'] = array(
			'type' => 'select',
			'label' => __( 'Allow the following users to delete the entry:', 'gravityview' ),
			'choices' => GravityView_Render_Settings::get_cap_choices( $template_id, $field_id, $context, $input_type ),
			'tooltip' => 'allow_edit_cap',
			'class' => 'widefat',
			'value' => 'read', // Default: entry creator
		);


		return array_merge( $add_option, $field_options );
	}


	/**
	 * Add Edit Link as a default field, outside those set in the Gravity Form form
	 *
	 * @since 1.5.1
	 * @param array $entry_default_fields Existing fields
	 * @param  string|array $form form_ID or form object
	 * @param  string $zone   Either 'single', 'directory', 'header', 'footer'
	 */
	function add_default_field( $entry_default_fields, $form = array(), $zone = '' ) {

		$entry_default_fields['delete_link'] = array(
			'label' => __('Delete Entry', 'gravityview'),
			'type' => 'delete_link',
			'desc'	=> __('A link to delete the entry. Respects the Delete Entry permissions.', 'gravityview'),
		);

		return $entry_default_fields;
	}

	/**
	 * Add Delete Entry Link to the Add Field dialog
	 * @since 1.5.1
	 * @param array $available_fields
	 */
	function add_available_field( $available_fields = array() ) {

		$available_fields['delete_link'] = array(
			'label_text' => __( 'Delete Entry', 'gravityview' ),
			'field_id' => 'delete_link',
			'label_type' => 'field',
			'input_type' => 'delete_link',
			'field_options' => NULL
		);

		return $available_fields;
	}

	/**
	 * Change wording for the Edit context to read Entry Creator
	 *
	 * @since 1.5.1
	 * @param  array 	   $visibility_caps        Array of capabilities to display in field dropdown.
	 * @param  string      $field_type  Type of field options to render (`field` or `widget`)
	 * @param  string      $template_id Table slug
	 * @param  float       $field_id    GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
	 * @param  string      $context     What context are we in? Example: `single` or `directory`
	 * @param  string      $input_type  (textarea, list, select, etc.)
	 * @return array                   Array of field options with `label`, `value`, `type`, `default` keys
	 */
	function modify_visibility_caps( $visibility_caps = array(), $template_id = '', $field_id = '', $context = '', $input_type = '' ) {

		$caps = $visibility_caps;

		// If we're configuring fields in the edit context, we want a limited selection
		if( $field_id === 'delete_link' ) {

			// Remove other built-in caps.
			unset( $caps['publish_posts'], $caps['gravityforms_view_entries'], $caps['delete_others_posts'] );

			$caps['read'] = _x('Entry Creator', 'User capability', 'gravityview');
		}

		return $caps;
	}

	/**
	 * Make sure there's an entry
	 *
	 * @since 1.5.1
	 * @param [type] $entry [description]
	 */
	function set_entry( $entry = null ) {
		$this->entry = empty( $entry ) ? GravityView_View::getInstance()->entries[0] : $entry;
	}

	/**
	 * Generate a consistent nonce key based on the Entry ID
	 *
	 * @since 1.5.1
	 * @param  int $entry_id Entry ID
	 * @return string           Key used to validate request
	 */
	static function get_nonce_key( $entry_id ) {
		return sprintf( 'delete_%s', $entry_id );
	}


	/**
	 * Generate a nonce link with the base URL of the current View embed
	 *
	 * We don't want to link to the single entry, because when deleted, there would be nothing to return to.
	 *
	 * @since 1.5.1
	 * @param  array      $entry Gravity Forms entry array
	 * @return string|null             If directory link is valid, the URL to process the delete request. Otherwise, `NULL`.
	 */
	static function get_delete_link( $entry ) {

		self::getInstance()->set_entry( $entry );

		$base = GravityView_API::directory_link( NULL, true );

		if( empty( $base ) ) {
			return NULL;
		}

		// Use the slug instead of the ID for consistent security
		$entry_slug = GravityView_API::get_entry_slug( $entry['id'], $entry );

        $view_id = gravityview_get_view_id();

		$actionurl = add_query_arg( array(
			'action'	=> 'delete',
			'entry_id'		=> $entry_slug,
            'view_id' => $view_id
		), $base );

		$url = wp_nonce_url( $actionurl, 'delete_'.$entry_slug, 'delete' );

		return $url;
	}


	/**
	 * Add a Delete button to the #publishing-action section of the Delete Entry form
	 *
	 * @since 1.5.1
	 * @param array $form    Gravity Forms form array
	 * @param array $entry   Gravity Forms entry array
	 * @param int $view_id GravityView View ID
	 */
	function add_delete_button( $form = array(), $entry = array(), $view_id = NULL ) {

		// Only show the link to those who are allowed to see it.
		if( !self::check_user_cap_delete_entry( $entry ) ) {
			return;
		}

		/**
		 * Show or hide the delete button in the Edit Entry screen
		 * @param boolean $show_entry Default: true
		 */
		$show_delete_button = apply_filters( 'gravityview/delete-entry/show-delete-button', true );

		// If the button is hidden by the filter, don't show.
		if( !$show_delete_button ) {
			return;
		}

		$attributes = array(
			'class' => 'btn btn-sm button button-small alignright pull-right btn-danger',
			'tabindex' => '5',
			'onclick' => self::get_confirm_dialog(),
		);

		echo gravityview_get_link( self::get_delete_link( $entry ), esc_attr__( 'Delete', 'gravityview' ), $attributes );

	}

	/**
	 * Handle the deletion request, if $_GET['action'] is set to "delete"
	 *
	 * 1. Check referrer validity
	 * 2. Make sure there's an entry with the slug of $_GET['entry_id']
	 * 3. If so, attempt to delete the entry. If not, set the error status
	 * 4. Remove `action=delete` from the URL
	 * 5. Redirect to the page using `wp_safe_redirect()`
	 *
	 * @since 1.5.1
	 * @uses GFAPI::delete_entry()
	 * @uses wp_safe_redirect()
	 * @return void
	 */
	function process_delete() {

		// If the form is submitted
		if( RGForms::get("action") === "delete") {

			$nonce_key = self::get_nonce_key( $_GET['entry_id'] );

			// Make sure it's a valid request
			check_admin_referer( $nonce_key, 'delete' );

			// Get the entry slug
			$entry_slug = esc_attr( $_GET['entry_id'] );

			// See if there's an entry there
			$entry = gravityview_get_entry( $entry_slug );

			if( $entry ) {

				$has_permission = $this->user_can_delete_entry( $entry );

				if( is_wp_error( $has_permission ) ) {

					$messages = array(
						'message' => urlencode( $has_permission->get_error_message() ),
						'status' => 'error',
					);

				} else {

					do_action('gravityview_log_debug', 'GravityView_Delete_Entry[process_delete] Starting delete entry: ', $entry );

					// Delete the entry
					$delete_response = GFAPI::delete_entry( $entry['id'] );

					do_action('gravityview_log_debug', 'GravityView_Delete_Entry[process_delete] Delete response: ', $delete_response );

					// GFAPI::delete_entry() returns a WP_Error on error
					if( is_wp_error( $delete_response ) ) {

						$messages = array(
							'message' => urlencode( $delete_response->get_error_message() ),
							'status' => 'error',
						);

					} else {

						$messages = array(
							'status' => 'success',
						);

					}

				}

			} else {

				do_action('gravityview_log_debug', 'GravityView_Delete_Entry[process_delete] Delete entry failed: there was no entry with the entry slug '.$entry_slug );

				$messages = array(
					'message' => urlencode( __('The entry does not exist.', 'gravityview') ),
					'status' => 'error',
				);
			}

			$redirect_to_base = esc_url( remove_query_arg( array('action') ) );
			$redirect_to = add_query_arg( $messages, $redirect_to_base );

			wp_safe_redirect( $redirect_to );

			exit();

		} // endif action is delete.

	} // process_delete


	/**
	 * Is the current nonce valid for editing the entry?
	 *
	 * @since 1.5.1
	 * @return boolean
	 */
	public function verify_nonce() {

		// No delete entry request was made
		if( empty( $_GET['entry_id'] ) ) {
			return false;
		}

		$nonce_key = self::get_nonce_key( $_GET['entry_id'] );

		return wp_verify_nonce( $_GET['delete'], $nonce_key );

	}

	/**
	 * Get the onclick attribute for the confirm dialogs that warns users before they delete an entry
	 *
	 * @since 1.5.1
	 * @return string HTML `onclick` attribute
	 */
	public static function get_confirm_dialog() {

		$confirm = __('Are you sure you want to delete this entry? This cannot be undone.', 'gravityview');

		/**
		 * Modify the confirmation text
		 * @var string
		 */
		$confirm = apply_filters( 'gravityview/delete-entry/confirm-text', $confirm );

		return 'return window.confirm(\''. esc_js( $confirm ) .'\');';
	}

	/**
	 * Check if the user can edit the entry
	 *
	 * - Is the nonce valid?
	 * - Does the user have the right caps for the entry
	 * - Is the entry in the trash?
	 *
	 * @since 1.5.1
	 * @param  array $entry Gravity Forms entry array
	 * @return boolean|WP_Error        True: can edit form. WP_Error: nope.
	 */
	function user_can_delete_entry( $entry = array() ) {

		$error = NULL;

		if( ! $this->verify_nonce() ) {
			$error = __( 'The link to delete this entry is not valid; it may have expired.', 'gravityview');
		}

		if( ! self::check_user_cap_delete_entry( $entry ) ) {
			$error = __( 'You do not have permission to delete this entry.', 'gravityview');
		}

		if( $entry['status'] === 'trash' ) {
			$error = __('You cannot delete the entry; it is already in the trash.', 'gravityview' );
		}

		// No errors; everything's fine here!
		if( empty( $error ) ) {
			return true;
		}

		do_action('gravityview_log_error', 'GravityView_Delete_Entry[user_can_delete_entry]' . $error );

		return new WP_Error( 'gravityview-delete-entry-permissions', $error );
	}


	/**
	 * checks if user has permissions to view the link or delete a specific entry
	 *
	 * @since 1.5.1
	 * @param  array $entry Gravity Forms entry array
	 * @param array $field Field settings (optional)
	 * @return bool
	 */
	public static function check_user_cap_delete_entry( $entry, $field = array() ) {
		$gravityview_view = GravityView_View::getInstance();

		// Or if they can delete any entries (as defined in Gravity Forms), we're good.
		if( GFCommon::current_user_can_any( 'gravityforms_delete_entries' ) ) {

			do_action('gravityview_log_debug', 'GravityView_Delete_Entry[check_user_cap_delete_entry] Current user has `gravityforms_delete_entries` capability.' );

			return true;
		}

		$current_user = wp_get_current_user();

		// If field options are passed, check if current user can view the link
		if( !empty( $field ) ) {

			// If capability is not defined, something is not right!
			if( empty( $field['allow_edit_cap'] ) ) {

				do_action( 'gravityview_log_error', 'GravityView_Delete_Entry[check_user_cap_delete_entry] Cannot read delete entry field caps', $field );

				return false;
			}

			if( GFCommon::current_user_can_any( $field['allow_edit_cap'] ) ) {

				// Do not return true if cap is read, as we need to check if the current user created the entry
				if( $field['allow_edit_cap'] !== 'read' ) {
					return true;
				}

			} else {

				do_action( 'gravityview_log_debug', sprintf( 'GravityView_Delete_Entry[check_user_cap_delete_entry] User %s is not authorized to view delete entry link ', $current_user->ID ) );

				return false;
			}

		}

		if( !isset( $entry['created_by'] ) ) {

			do_action('gravityview_log_error', 'GravityView_Delete_Entry[check_user_cap_delete_entry] Entry `created_by` doesn\'t exist.');

			return false;
		}

		// Only checks user_delete view option if view is already set
		if( $gravityview_view->getViewId() ) {

			$user_delete = $gravityview_view->getAtts('user_delete');

			if( empty( $user_delete ) ) {

				do_action('gravityview_log_debug', 'GravityView_Delete_Entry[check_user_cap_delete_entry] User Delete is disabled. Returning false.' );

				return false;
			}
		}

		// If the logged-in user is the same as the user who created the entry, we're good.
		if( is_user_logged_in() && intval( $current_user->ID ) === intval( $entry['created_by'] ) ) {

			do_action('gravityview_log_debug', sprintf( 'GravityView_Delete_Entry[check_user_cap_delete_entry] User %s created the entry.', $current_user->ID ) );

			return true;
		}

		return false;
	}


	/**
	 * After processing delete entry, the user will be redirected to the referring View or embedded post/page. Display a message on redirection.
	 *
	 * If success, there will be `status` URL parameters `status=>success`
	 * If an error, there will be `status` and `message` URL parameters `status=>error&message=example`
	 *
	 * @since 1.5.1
	 * @return void
	 */
	function display_message() {

		if( empty( $_GET['status'] ) ) {
			return;
		}

		$message = rgget('message');
		$message = urldecode( stripslashes_deep( $message ) );
		$class = '';

		switch ( $_GET['status'] ) {
			case 'error':
				$class = ' gv-error error';
				$error_message = __('There was an error deleting the entry: %s', 'gravityview');
				$message = sprintf( $error_message, $message );
				break;

			default:
				$message = __('The entry was successfully deleted.', 'gravityview');
				break;
		}

		// DISPLAY ERROR/SUCCESS MESSAGE
		echo '<div class="gv-notice'.esc_attr( $class ) .'">'. $message .'</div>';
	}


} // end class

new GravityView_Delete_Entry;

