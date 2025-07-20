<?php
/**
 * @file class-gravityview-entry-approval.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      https://www.gravitykit.com
 * @copyright Copyright 2016, Katz Web Services, Inc.
 *
 * @since 1.18
 */

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Generate linked list output for a list of entries.
 *
 * @since 1.18
 */
class GravityView_Entry_Approval {

	/**
	 * @var string Key used to store approval status in the Gravity Forms entry meta table
	 */
	const meta_key = 'is_approved';

	public function __construct() {
		$this->add_hooks();
	}

	/**
	 * Add actions and filters related to entry approval
	 *
	 * @return void
	 */
	private function add_hooks() {

		// in case entry is edited (on admin or frontend)
		add_action( 'gform_after_update_entry', array( $this, 'after_update_entry_update_approved_meta' ), 10, 2 );

		// when using the User opt-in field, check on entry submission
		add_action( 'gform_after_submission', array( $this, 'after_submission' ), 10, 2 );

		// process ajax approve entry requests
		add_action( 'wp_ajax_gv_update_approved', array( $this, 'ajax_update_approved' ) );

		// autounapprove
		add_action( 'gravityview/edit_entry/after_update', array( __CLASS__, 'autounapprove' ), 10, 4 );

		add_filter( 'gform_notification_events', array( __CLASS__, 'add_approval_notification_events' ), 10, 2 );

		add_action( 'gravityview/approve_entries/approved', array( $this, '_trigger_notifications' ) );
		add_action( 'gravityview/approve_entries/disapproved', array( $this, '_trigger_notifications' ) );
		add_action( 'gravityview/approve_entries/unapproved', array( $this, '_trigger_notifications' ) );
		add_action( 'gravityview/approve_entries/updated', array( $this, '_trigger_notifications' ) );

		add_action( 'check_admin_referer', [ $this, 'resend_gf_notifications' ], 10, 2 );

		add_filter( 'gform_field_filters', [ $this, 'add_approval_field_filter' ] );

	}

	/**
	 * Adds approval status filter to the filter list (Export Entries conditional logic).
	 *
	 * @since 2.34
	 *
	 * @param array $filters The existing filters.
	 * @param array $form    The form array.
	 *
	 * @return array The modified filters.
	 */
	public function add_approval_field_filter( $filters ) {
		$filters[] = [
			'key'             => 'is_approved',
			'text'            => esc_html__( 'Approval Status', 'gk-gravityview' ),
			'preventMultiple' => false,
			'operators'       => [ 'is' ],
			'values'          => [
				[
					'value' => '1',
					'text'  => esc_html__( 'Approved', 'gk-gravityview' ),
				],
				[
					'value' => '2',
					'text'  => esc_html__( 'Disapproved', 'gk-gravityview' ),
				],
				[
					'value' => '3',
					'text'  => esc_html__( 'Unapproved', 'gk-gravityview' ),
				],
			],
		];

		return $filters;
	}

	/**
	 * Passes approval notification and action hook to the send_notifications method
	 *
	 * @internal Developers, do not use!
	 *
	 * @since    2.1
	 *
	 * @see      GravityView_Notifications::send_notifications()
	 *
	 * @param int   $entry_id ID of entry being updated
	 * @param array $entry    The entry object.
	 *
	 * @return void
	 */
	public function _trigger_notifications( $entry_id = 0, $entry = [] ) {
		if ( did_action( 'gform_entry_created' ) && 'gravityview/approve_entries/updated' === current_action() ) {
			return;
		}

		GravityView_Notifications::send_notifications( (int) $entry_id, (string) current_action(), $entry );
	}

	/**
	 * Adds entry approval status change custom notification events
	 *
	 * @since 2.1
	 *
	 * @param array $notification_events The notification events.
	 * @param array $form The current form.
	 */
	public static function add_approval_notification_events( $notification_events = array(), $form = array() ) {

		$notification_events['gravityview/approve_entries/approved']    = 'GravityView - ' . esc_html_x( 'Entry is approved', 'The title for an event in a notifications drop down list.', 'gk-gravityview' );
		$notification_events['gravityview/approve_entries/disapproved'] = 'GravityView - ' . esc_html_x( 'Entry is disapproved', 'The title for an event in a notifications drop down list.', 'gk-gravityview' );
		$notification_events['gravityview/approve_entries/unapproved']  = 'GravityView - ' . esc_html_x( 'Entry approval is reset', 'The title for an event in a notifications drop down list.', 'gk-gravityview' );
		$notification_events['gravityview/approve_entries/updated']     = 'GravityView - ' . esc_html_x( 'Entry approval is changed', 'The title for an event in a notifications drop down list.', 'gk-gravityview' );

		return $notification_events;
	}

	/**
	 * Get the approval status for an entry
	 *
	 * @since 1.18
	 * @uses GVCommon::get_entry_id() Accepts entry slug or entry ID
	 *
	 * @param array|int|string $entry Entry array, entry slug, or entry ID
	 * @param string           $value_or_label "value" or "label" (default: "label")
	 *
	 * @return bool|string Return the label or value of entry approval
	 */
	public static function get_entry_status( $entry, $value_or_label = 'label' ) {

		$entry_id = is_array( $entry ) ? $entry['id'] : GVCommon::get_entry_id( $entry, true );

		$status = gform_get_meta( $entry_id, self::meta_key );

		$status = GravityView_Entry_Approval_Status::maybe_convert_status( $status );

		if ( 'value' === $value_or_label ) {
			return $status;
		}

		return GravityView_Entry_Approval_Status::get_label( $status );
	}

	/**
	 * Approve/Disapprove entries using the × or ✓ icons in the GF Entries screen
	 *
	 * @uses wp_send_json_error()
	 * @uses wp_send_json_success()
	 *
	 * Expects a $_POST request with the following $_POST keys and values:
	 *
	 * @global array $_POST {
	 * @type int $form_id ID of the form connected to the entry being updated
	 * @type string|int $entry_slug The ID or slug of the entry being updated
	 * @type string $approved The value of the entry approval status {@see GravityView_Entry_Approval_Status::is_valid() }
	 * }
	 *
	 * @return void Prints result using wp_send_json_success() and wp_send_json_error()
	 */
	public function ajax_update_approved() {

		$form_id = intval( \GV\Utils::_POST( 'form_id' ) );

		// We always want requests from the admin to allow entry IDs, but not from the frontend
		// There's another nonce sent when approving entries in the admin that we check
		$force_entry_ids = \GV\Utils::_POST( 'admin_nonce' ) && wp_verify_nonce( \GV\Utils::_POST( 'admin_nonce' ), 'gravityview_admin_entry_approval' );

		$entry_id = GVCommon::get_entry_id( \GV\Utils::_POST( 'entry_slug' ), $force_entry_ids );

		$approval_status = \GV\Utils::_POST( 'approved' );

		$nonce = \GV\Utils::_POST( 'nonce' );

		// Valid status
		if ( ! GravityView_Entry_Approval_Status::is_valid( $approval_status ) ) {

			gravityview()->log->error( 'Invalid approval status', array( 'data' => $_POST ) );

			$result = new WP_Error( 'invalid_status', __( 'The request was invalid. Refresh the page and try again.', 'gk-gravityview' ) );

		}

		// Valid values
		elseif ( empty( $entry_id ) || empty( $form_id ) ) {

			gravityview()->log->error( 'entry_id or form_id are empty.', array( 'data' => $_POST ) );

			$result = new WP_Error( 'empty_details', __( 'The request was invalid. Refresh the page and try again.', 'gk-gravityview' ) );

		}

		// Valid nonce
		elseif ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'gravityview_entry_approval' ) ) {

			gravityview()->log->error( 'Security check failed.', array( 'data' => $_POST ) );

			$result = new WP_Error( 'invalid_nonce', __( 'The request was invalid. Refresh the page and try again.', 'gk-gravityview' ) );

		}

		// Has capability
		elseif ( ! GVCommon::has_cap( 'gravityview_moderate_entries', $entry_id ) ) {

			gravityview()->log->error( 'User does not have the `gravityview_moderate_entries` capability.' );

			$result = new WP_Error( 'Missing Cap: gravityview_moderate_entries', __( 'You do not have permission to edit this entry.', 'gk-gravityview' ) );

		}

		// All checks passed
		else {

			$result = self::update_approved( $entry_id, $approval_status, $form_id );

		}

		if ( is_wp_error( $result ) ) {
			gravityview()->log->error( 'Error updating approval: {error}', array( 'error' => $result->get_error_message() ) );

			wp_send_json_error( $result );
		}

		$current_status = self::get_entry_status( $entry_id, 'value' );

		wp_send_json_success(
			array(
				'status' => $current_status,
			)
		);
	}

	/**
	 * Update the is_approved meta whenever the entry is submitted (and it contains a User Opt-in field)
	 *
	 * @since 1.16.6
	 * @since 2.0.14 Set the default approval `is_approved` value upon submission
	 *
	 * @param $entry array Gravity Forms entry object
	 * @param $form array Gravity Forms form object
	 */
	public function after_submission( $entry, $form ) {

		/**
		 * Modify whether to run the after_submission process.
		 *
		 * @since 2.3
		 * @param bool $process_after_submission default: true
		 */
		$process_after_submission = apply_filters( 'gravityview/approve_entries/after_submission', true );

		if ( ! $process_after_submission ) {
			return;
		}

		$default_status = GravityView_Entry_Approval_Status::UNAPPROVED;

		/**
		 * Modify the default approval status for newly submitted entries.
		 *
		 * @since 2.0.14
		 * @param int $default_status See GravityView_Entry_Approval_Status() for valid statuses.
		 */
		$filtered_status = apply_filters( 'gravityview/approve_entries/after_submission/default_status', $default_status );

		if ( GravityView_Entry_Approval_Status::is_valid( $filtered_status ) ) {
			$default_status = $filtered_status;
		} else {
			gravityview()->log->error( 'Invalid approval status returned by `gravityview/approve_entries/after_submission/default_status` filter: {status}', array( 'status' => $filtered_status ) );
		}

		// Set default
		self::update_approved_meta( $entry['id'], $default_status, $entry['form_id'] );

		// Then check for if there is an approval column, and use that value instead
		$this->after_update_entry_update_approved_meta( $form, $entry['id'] );
	}

	/**
	 * Update the is_approved meta whenever the entry is updated
	 *
	 * @since 1.7.6.1 Was previously named `update_approved_meta`
	 *
	 * @param  array $form     Gravity Forms form array
	 * @param  int   $entry_id ID of the Gravity Forms entry
	 * @return void
	 */
	public function after_update_entry_update_approved_meta( $form, $entry_id = null ) {

		$approved_column = self::get_approved_column( $form['id'] );

		/**
		 * If the form doesn't contain the approve field, don't assume anything.
		 */
		if ( empty( $approved_column ) ) {
			return;
		}

		$entry = GFAPI::get_entry( $entry_id );

		// Determine new approval status
		$existing_status = GravityView_Entry_Approval::get_entry_status( $entry_id, 'value' );

		if ( '' === \GV\Utils::get( $entry, $approved_column ) ) {
			$new_status = GravityView_Entry_Approval_Status::DISAPPROVED;
		} else {
			$new_status = GravityView_Entry_Approval_Status::APPROVED;
		}

		/**
		 * Filter the approval status on entry update.
		 *
		 * @filter `gravityview/approve_entries/update_unapproved_meta`
		 *
		 * @param string $new_status The approval status.
		 * @param array $form The form.
		 * @param array $entry The entry.
		 */
		$new_status = apply_filters( 'gravityview/approve_entries/update_unapproved_meta', $new_status, $form, $entry );

		// Update meta only if status has changed.
		if ( (int) $existing_status !== (int) $new_status ) {
			self::update_approved_meta( $entry_id, $new_status, $form['id'] );
		}
	}

	/**
	 * Process a bulk of entries to update the approve field/property
	 *
	 * @since 1.18 Moved to GravityView_Entry_Approval
	 * @since 1.18 Made public
	 *
	 * @static
	 * @param array|boolean $entries If array, array of entry IDs that are to be updated. If true: update all entries.
	 * @param int           $approved Approved status. If `0`: unapproved, if not empty, `Approved`
	 * @param int           $form_id The Gravity Forms Form ID
	 * @return boolean|null True: successfully updated all entries. False: there was an error updating at least one entry. NULL: an error occurred (see log)
	 */
	public static function update_bulk( $entries = array(), $approved = 0, $form_id = 0 ) {

		if ( empty( $entries ) || ( true !== $entries && ! is_array( $entries ) ) ) {
			gravityview()->log->error( 'Entries were empty or malformed.', array( 'data' => $entries ) );
			return null;
		}

		if ( ! GVCommon::has_cap( 'gravityview_moderate_entries' ) ) {
			gravityview()->log->error( 'User does not have the `gravityview_moderate_entries` capability.' );
			return null;
		}

		if ( ! GravityView_Entry_Approval_Status::is_valid( $approved ) ) {
			gravityview()->log->error( 'Invalid approval status', array( 'data' => $approved ) );
			return null;
		}

		// calculate approved field id once instead of looping through in the update_approved() method
		$approved_column_id = self::get_approved_column( $form_id );

		$success = true;
		foreach ( $entries as $entry_id ) {
			$update_success = self::update_approved( (int) $entry_id, $approved, $form_id, $approved_column_id );

			if ( ! $update_success ) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * update_approved function.
	 *
	 * @since 1.18 Moved to GravityView_Entry_Approval class
	 *
	 * @static
	 * @param int $entry_id (default: 0)
	 * @param int $approved (default: 2)
	 * @param int $form_id (default: 0)
	 * @param int $approvedcolumn (default: 0)
	 *
	 * @return boolean True: It worked; False: it failed
	 */
	public static function update_approved( $entry_id = 0, $approved = 2, $form_id = 0, $approvedcolumn = 0 ) {

		if ( ! class_exists( 'GFAPI' ) ) {
			gravityview()->log->error( 'GFAPI does not exist' );
			return false;
		}

		if ( ! GravityView_Entry_Approval_Status::is_valid( $approved ) ) {
			gravityview()->log->error( 'Not a valid approval value.' );
			return false;
		}

		$approved = GravityView_Entry_Approval_Status::maybe_convert_status( $approved );

		$entry = GFAPI::get_entry( $entry_id );

		if ( is_wp_error( $entry ) ) {
			gravityview()->log->error( 'Entry does not exist' );
			return false;
		}

		// If the form has an Approve/Reject field, update that value
		$result = self::update_approved_column( $entry_id, $approved, $form_id, $approvedcolumn );

		if ( is_wp_error( $result ) ) {
			gravityview()->log->error( 'Entry approval not updated: {error}', array( 'error' => $result->get_error_message() ) );
			return false;
		}

		$form_id = intval( $form_id );

		// Update the entry meta
		self::update_approved_meta( $entry_id, $approved, $form_id );

		// add note to entry if approval field updating worked or there was no approved field
		// There's no validation for the meta
		if ( true === $result ) {

			// Add an entry note
			self::add_approval_status_updated_note( $entry_id, $approved );

			/**
			 * Destroy the cache for this form
			 *
			 * @see class-cache.php
			 * @since 1.5.1
			 */
			do_action( 'gravityview_clear_form_cache', $form_id );

		}

		return $result;
	}

	/**
	 * Add a note when an entry is approved
	 *
	 * @see GravityView_Entry_Approval::update_approved
	 *
	 * @since 1.18
	 *
	 * @param int $entry_id Gravity Forms entry ID
	 * @param int $approved Approval status
	 *
	 * @return false|int|WP_Error Note ID if successful; WP_Error if error when adding note, FALSE if note not updated because of `gravityview/approve_entries/add-note` filter or `GravityView_Entry_Notes` class not existing
	 */
	private static function add_approval_status_updated_note( $entry_id, $approved = 0 ) {
		$note = '';

		switch ( $approved ) {
			case GravityView_Entry_Approval_Status::APPROVED:
				$note = __( 'Approved the Entry for GravityView', 'gk-gravityview' );
				break;
			case GravityView_Entry_Approval_Status::UNAPPROVED:
				$note = __( 'Reset Entry approval for GravityView', 'gk-gravityview' );
				break;
			case GravityView_Entry_Approval_Status::DISAPPROVED:
				$note = __( 'Disapproved the Entry for GravityView', 'gk-gravityview' );
				break;
		}

		/**
		 * Add a note when the entry has been approved or disapproved?
		 *
		 * @since 1.16.3
		 * @param bool $add_note True: Yep, add that note! False: Do not, under any circumstances, add that note!
		 */
		$add_note = apply_filters( 'gravityview/approve_entries/add-note', true );

		$note_id = false;

		if ( $add_note && class_exists( 'GravityView_Entry_Notes' ) ) {

			$current_user = wp_get_current_user();

			$note_id = GravityView_Entry_Notes::add_note( $entry_id, $current_user->ID, $current_user->display_name, $note );
		}

		return $note_id;
	}

	/**
	 * Update the Approve/Disapproved field value
	 *
	 * @param  int    $entry_id ID of the Gravity Forms entry
	 * @param  string $status String whether entry is approved or not. `0` for not approved, `Approved` for approved.
	 * @param int    $form_id ID of the form of the entry being updated. Improves query performance.
	 * @param string $approvedcolumn Gravity Forms Field ID
	 *
	 * @return true|WP_Error
	 */
	private static function update_approved_column( $entry_id = 0, $status = '0', $form_id = 0, $approvedcolumn = 0 ) {

		if ( empty( $approvedcolumn ) ) {
			$approvedcolumn = self::get_approved_column( $form_id );
		}

		if ( empty( $approvedcolumn ) ) {
			return true;
		}

		if ( ! GravityView_Entry_Approval_Status::is_valid( $status ) ) {
			return new WP_Error( 'invalid_status', 'Invalid entry approval status', $status );
		}

		// get the entry
		$entry = GFAPI::get_entry( $entry_id );

		// Entry doesn't exist
		if ( is_wp_error( $entry ) ) {
			return $entry;
		}

		$status = GravityView_Entry_Approval_Status::maybe_convert_status( $status );

		$new_value = '';
		if ( GravityView_Entry_Approval_Status::APPROVED === $status ) {
			$new_value = self::get_approved_column_input_label( $form_id, $approvedcolumn );
		}

		// update entry
		$entry[ "{$approvedcolumn}" ] = $new_value;

		/**
		 * Note: GFAPI::update_entry() doesn't trigger `gform_after_update_entry`, so we trigger updating the meta ourselves
		 *
		 * @see GravityView_Entry_Approval::after_update_entry_update_approved_meta
		 * @var true|WP_Error $result
		 */
		$result = GFAPI::update_entry( $entry );

		return $result;
	}

	/**
	 * Get the value for the approved field checkbox
	 *
	 * When approving a field via the entry meta, use the correct value for the new approved column input
	 *
	 * @since 1.19
	 *
	 * @param array|int $form Form ID or form array
	 * @param string    $approved_column Approved column field ID
	 *
	 * @return string|null
	 */
	private static function get_approved_column_input_label( $form, $approved_column ) {

		$field = gravityview_get_field( $form, $approved_column );

		// If the user has enabled a different value than the label (for some reason), use it.
		// This is highly unlikely
		if ( is_array( $field->choices ) && ! empty( $field->choices ) ) {
			return isset( $field->choices[0]['value'] ) ? $field->choices[0]['value'] : $field->choices[0]['text'];
		}

		// Otherwise, fall back on the inputs array
		if ( is_array( $field->inputs ) && ! empty( $field->inputs ) ) {
			return $field->inputs[0]['label'];
		}

		return null;
	}

	/**
	 * Update the `is_approved` entry meta value
	 *
	 * @since 1.7.6.1 `after_update_entry_update_approved_meta` was previously to be named `update_approved_meta`
	 * @since 1.17.1 Added $form_id parameter
	 *
	 * @param  int    $entry_id ID of the Gravity Forms entry
	 * @param  string $status String whether entry is approved or not. `0` for not approved, `Approved` for approved.
	 * @param int    $form_id ID of the form of the entry being updated. Improves query performance.
	 *
	 * @return void
	 */
	private static function update_approved_meta( $entry_id, $status, $form_id = 0 ) {

		if ( ! GravityView_Entry_Approval_Status::is_valid( $status ) ) {
			gravityview()->log->error( '$is_approved not valid value', array( 'data' => $status ) );
			return;
		}

		if ( ! function_exists( 'gform_update_meta' ) ) {
			gravityview()->log->error( '`gform_update_meta` does not exist.' );
			return;
		}

		$status = GravityView_Entry_Approval_Status::maybe_convert_status( $status );

		// update entry meta
		gform_update_meta( $entry_id, self::meta_key, $status, $form_id );

		/**
		 * Triggered when an entry approval is updated.
		 *
		 * @since 1.7.6.1
		 * @param  int $entry_id ID of the Gravity Forms entry
		 * @param  string|int $status String whether entry is approved or not. See GravityView_Entry_Approval_Status for valid statuses.
		 */
		do_action( 'gravityview/approve_entries/updated', $entry_id, $status );

		$action = GravityView_Entry_Approval_Status::get_key( $status );

		/**
		 * Triggered when an entry approval is set. {$action} can be 'approved', 'unapproved', or 'disapproved'.
		 * Note: If you want this to work with Bulk Actions, run in a plugin rather than a theme; the bulk updates hook runs before themes are loaded.
		 *
		 * @since 1.7.6.1
		 * @since 1.18 Added "unapproved"
		 * @param  int $entry_id ID of the Gravity Forms entry
		 */
		do_action( 'gravityview/approve_entries/' . $action, $entry_id );
	}

	/**
	 * Calculate the approve field.input id
	 *
	 * @static
	 * @param mixed $form GF Form or Form ID
	 * @return false|null|string Returns the input ID of the approved field. Returns NULL if no approved fields were found. Returns false if $form_id wasn't set.
	 */
	public static function get_approved_column( $form ) {

		if ( empty( $form ) ) {
			return null;
		}

		if ( ! is_array( $form ) ) {
			$form = GVCommon::get_form( $form );
		}

		$approved_column_id = null;

		/**
		 * @var string $key
		 * @var GF_Field $field
		 */
		foreach ( $form['fields'] as $key => $field ) {

			$inputs = $field->get_entry_inputs();

			if ( ! empty( $field->gravityview_approved ) ) {
				if ( ! empty( $inputs ) && ! empty( $inputs[0]['id'] ) ) {
					$approved_column_id = $inputs[0]['id'];
					break;
				}
			}

			// Note: This is just for backward compatibility from GF Directory plugin and old GV versions - when using i18n it may not work..
			if ( 'checkbox' === $field->type && ! empty( $inputs ) ) {
				foreach ( $inputs as $input ) {
					if ( 'approved' === strtolower( $input['label'] ) ) {
						$approved_column_id = $input['id'];
						break;
					}
				}
			}
		}

		return $approved_column_id;
	}

	/**
	 * Maybe unapprove entry on edit.
	 *
	 * Called from gravityview/edit_entry/after_update
	 *
	 * @param array                         $form Gravity Forms form array
	 * @param string                        $entry_id Numeric ID of the entry that was updated
	 * @param GravityView_Edit_Entry_Render $edit This object
	 * @param GravityView_View_Data         $gv_data The View data
	 *
	 * @return void
	 */
	public static function autounapprove( $form, $entry_id, $edit, $gv_data ) {

		$view_keys = array_keys( $gv_data->get_views() );

		$view = \GV\View::by_id( $view_keys[0] );

		if ( ! $view->settings->get( 'unapprove_edit' ) ) {
			return;
		}

		if ( GVCommon::has_cap( 'gravityview_moderate_entries' ) ) {
			return;
		}

		/**
		 * @filter `gravityview/approve_entries/autounapprove/status`
		 * @since 2.2.2
		 * @param int|false $approval_status Approval status integer, or false if you want to not update status. Use GravityView_Entry_Approval_Status constants. Default: 3 (GravityView_Entry_Approval_Status::UNAPPROVED)
		 * @param array $form Gravity Forms form array
		 * @param string $entry_id Numeric ID of the entry that was updated
		 * @param \GV\View $view Current View where the entry was edited
		 */
		$approval_status = apply_filters( 'gravityview/approve_entries/autounapprove/status', GravityView_Entry_Approval_Status::UNAPPROVED, $form, $entry_id, $view );

		// Allow returning false to exit
		if ( false === $approval_status ) {
			return;
		}

		if ( ! GravityView_Entry_Approval_Status::is_valid( $approval_status ) ) {
			$approval_status = GravityView_Entry_Approval_Status::UNAPPROVED;
		}

		self::update_approved_meta( $entry_id, $approval_status, $form['id'] );
	}

	/**
	 * Where should the popover be placed?
	 *
	 * @since 2.3.1
	 *
	 * @return string Where to place the popover; 'right' (default ltr), 'left' (default rtl), 'top', or 'bottom'
	 */
	public static function get_popover_placement() {

		$placement = is_rtl() ? 'left' : 'right';

		/**
		 * Where should the popover be placed?
		 *
		 * @since 2.3.1
		 * @param string $placement Where to place the popover; 'right' (default ltr), 'left' (default rtl), 'top', or 'bottom'
		 */
		$placement = apply_filters( 'gravityview/approve_entries/popover_placement', $placement );

		return $placement;
	}

	/**
	 * Get HTML template for a popover used to display approval statuses
	 *
	 * @since 2.3.1
	 *
	 * @internal For internal use only!
	 *
	 * @return string HTML code
	 */
	public static function get_popover_template() {

		$choices = GravityView_Entry_Approval_Status::get_all();

		return <<<TEMPLATE
<a href="#" data-approved="{$choices['approved']['value']}" aria-role="button"  aria-live="polite" class="gv-approval-toggle gv-approval-approved popover" title="{$choices['approved']['action']}"><span class="screen-reader-text">{$choices['approved']['action']}</span></a>
<a href="#" data-approved="{$choices['disapproved']['value']}" aria-role="button"  aria-live="polite" class="gv-approval-toggle gv-approval-disapproved popover" title="{$choices['disapproved']['action']}"><span class="screen-reader-text">{$choices['disapproved']['action']}</span></a>
<a href="#" data-approved="{$choices['unapproved']['value']}" aria-role="button"  aria-live="polite" class="gv-approval-toggle gv-approval-unapproved popover" title="{$choices['unapproved']['action']}"><span class="screen-reader-text">{$choices['unapproved']['action']}</span></a>
TEMPLATE;
	}

	/**
	 * Makes "resend notifications" work with GravityView approval status filtering.
	 * Workaround for the lack of a filter to override the search criteria used by {@see GFForms::resend_notifications()}.
	 *
	 * @used-by check_admin_referer action
	 *
	 * @since 2.30.0
	 *
	 * @param string $action The action being performed.
	 * @param int    $result The result of the action check.
	 *
	 * @return void
	 */
	public function resend_gf_notifications( $action, $result ) {
		if ( 'gf_resend_notifications' !== $action || 1 !== $result ) {
			return;
		}

		$form_id = (int) rgpost( 'formId' );
		$leads   = rgpost( 'leadIds' );
		$filter  = rgpost( 'filter' );

		$filter_to_approval_status_map = [
			'gv_approved'    => GravityView_Entry_Approval_Status::APPROVED,
			'gv_unapproved'  => GravityView_Entry_Approval_Status::UNAPPROVED,
			'gv_disapproved' => GravityView_Entry_Approval_Status::DISAPPROVED,
		];

		if ( ! isset( $filter_to_approval_status_map[ $filter ] ) || ! $form_id || 0 !== (int) $leads ) {
			return;
		}

		$search_criteria = [
			'status'        => 'active',
			'field_filters' => [
				[
					'key'   => 'is_approved',
					'value' => $filter_to_approval_status_map[ $filter ],
				],
			],
		];

		$_POST['leadIds'] = GFFormsModel::search_lead_ids( $form_id, $search_criteria );
	}
}

new GravityView_Entry_Approval();
