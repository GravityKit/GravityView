<?php
/**
 * @file class-gravityview-entry-approval.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      https://gravityview.co
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
		add_action( 'gform_after_update_entry', array( $this, 'after_update_entry_update_approved_meta' ), 10, 2);

		// when using the User opt-in field, check on entry submission
		add_action( 'gform_after_submission', array( $this, 'after_submission' ), 10, 2 );

		// process ajax approve entry requests
		add_action('wp_ajax_gv_update_approved', array( $this, 'ajax_update_approved'));

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

		$form_id = intval( rgpost('form_id') );

		$entry_id = GVCommon::get_entry_id( rgpost('entry_slug') );

		$approval_status = rgpost('approved');

		$nonce = rgpost('nonce');

		// Valid status
		if( ! GravityView_Entry_Approval_Status::is_valid( $approval_status ) ) {

			do_action( 'gravityview_log_error', __METHOD__ . ': Invalid approval status', $_POST );

			$result = new WP_Error( 'invalid_status', __( 'The request was invalid. Refresh the page and try again.', 'gravityview' ) );

		}

		// Valid values
		elseif ( empty( $entry_id ) || empty( $form_id ) ) {

			do_action( 'gravityview_log_error', __METHOD__ . ' entry_id or form_id are empty.', $_POST );

			$result = new WP_Error( 'empty_details', __( 'The request was invalid. Refresh the page and try again.', 'gravityview' ) );

		}

		// Valid nonce
		else if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'gravityview_entry_approval' ) ) {

			do_action( 'gravityview_log_error', __METHOD__ . ' Security check failed.', $_POST );

			$result = new WP_Error( 'invalid_nonce', __( 'The request was invalid. Refresh the page and try again.', 'gravityview' ) );

		}

		// Has capability
		elseif ( ! GVCommon::has_cap( 'gravityview_moderate_entries', $entry_id ) ) {

			do_action( 'gravityview_log_error', __METHOD__ . ' User does not have the `gravityview_moderate_entries` capability.' );

			$result = new WP_Error( 'Missing Cap: gravityview_moderate_entries', __( 'You do not have permission to edit this entry.', 'gravityview') );

		}

		// All checks passed
		else {

			$result = self::update_approved( $entry_id, $approval_status, $form_id );

		}

		if ( is_wp_error( $result ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . ' Error updating approval: ' . $result->get_error_message() );

			wp_send_json_error( $result );
		}

		wp_send_json_success();
	}

	/**
	 * Update the is_approved meta whenever the entry is submitted (and it contains a User Opt-in field)
	 *
	 * @since 1.16.6
	 *
	 * @param $entry array Gravity Forms entry object
	 * @param $form array Gravity Forms form object
	 */
	public function after_submission( $entry, $form ) {
		$this->after_update_entry_update_approved_meta( $form , $entry['id'] );
	}

	/**
	 * Update the is_approved meta whenever the entry is updated
	 *
	 * @since 1.7.6.1 Was previously named `update_approved_meta`
	 *
	 * @param  array $form     Gravity Forms form array
	 * @param  int $entry_id ID of the Gravity Forms entry
	 * @return void
	 */
	public function after_update_entry_update_approved_meta( $form, $entry_id = NULL ) {

		$approved_column = self::get_approved_column( $form['id'] );

		/**
		 * If the form doesn't contain the approve field, don't assume anything.
		 */
		if( empty( $approved_column ) ) {
			return;
		}

		$entry = GFAPI::get_entry( $entry_id );

		self::update_approved_meta( $entry_id, $entry[ (string)$approved_column ], $form['id'] );
	}

	/**
	 * Process a bulk of entries to update the approve field/property
	 *
	 * @since 1.18 Moved to GravityView_Entry_Approval
	 * @since 1.18 Made public
	 *
	 * @access public
	 * @static
	 * @param array|boolean $entries If array, array of entry IDs that are to be updated. If true: update all entries.
	 * @param int $approved Approved status. If `0`: unapproved, if not empty, `Approved`
	 * @param int $form_id The Gravity Forms Form ID
	 * @return boolean|void
	 */
	public static function update_bulk( $entries = array(), $approved, $form_id ) {

		if( empty($entries) || ( $entries !== true && !is_array($entries) ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . ' Entries were empty or malformed.', $entries );
			return false;
		}

		if( ! GVCommon::has_cap( 'gravityview_moderate_entries' ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . ' User does not have the `gravityview_moderate_entries` capability.' );
			return false;
		}

		$status = GravityView_Entry_Approval_Status::is_disapproved( $approved ) ? GravityView_Entry_Approval_Status::Disapproved : GravityView_Entry_Approval_Status::Approved;

		// calculate approved field id once instead of looping through in the update_approved() method
		$approved_column_id = self::get_approved_column( $form_id );

		foreach( $entries as $entry_id ) {
			self::update_approved( (int)$entry_id, $status, $form_id, $approved_column_id );
		}
	}

	/**
	 * update_approved function.
	 *
	 * @since 1.18 Moved to GravityView_Entry_Approval class
	 *
	 * @access public
	 * @static
	 * @param int $entry_id (default: 0)
	 * @param int $approved (default: 0)
	 * @param int $form_id (default: 0)
	 * @param int $approvedcolumn (default: 0)
	 *
	 * @return boolean True: It worked; False: it failed
	 */
	public static function update_approved( $entry_id = 0, $approved = 0, $form_id = 0, $approvedcolumn = 0 ) {

		if( !class_exists( 'GFAPI' ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . 'GFAPI does not exist' );
			return false;
		}

		if( ! GravityView_Entry_Approval_Status::is_valid( $approved ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . ': Not a valid approval value.' );
			return false;
		}

		$result = self::update_approved_column( $entry_id, $approved, $form_id, $approvedcolumn );

		/**
		 * GFAPI::update_entry() doesn't trigger `gform_after_update_entry`, so we trigger updating the meta ourselves.
		 */
		self::update_approved_meta( $entry_id, $approved, $form_id );

		// add note to entry if approval field updating worked or there was no approved field
		// There's no validation for the meta
		if( true === $result ) {

			$note = empty( $approved ) ? __( 'Disapproved the Entry for GravityView', 'gravityview' ) : __( 'Approved the Entry for GravityView', 'gravityview' );

			/**
			 * @filter `gravityview/approve_entries/add-note` Add a note when the entry has been approved or disapproved?
			 * @since 1.16.3
			 * @param bool $add_note True: Yep, add that note! False: Do not, under any circumstances, add that note!
			 */
			$add_note = apply_filters( 'gravityview/approve_entries/add-note', true );

			if( $add_note && class_exists( 'GravityView_Entry_Notes' ) ) {
				$current_user = wp_get_current_user();
				GravityView_Entry_Notes::add_note( $entry_id, $current_user->ID, $current_user->display_name, $note );
			}

			/**
			 * Destroy the cache for this form
			 * @see class-cache.php
			 * @since 1.5.1
			 */
			do_action( 'gravityview_clear_form_cache', $form_id );

		} else if( is_wp_error( $result ) ) {

			do_action( 'gravityview_log_error', __METHOD__ . sprintf( ' - Entry approval not updated: %s', $result->get_error_message() ) );

			$result = false;
		}

		return $result;
	}

	/**
	 * Update the Approve/Disapproved field value
	 *
	 * @param  int $entry_id ID of the Gravity Forms entry
	 * @param  string $status String whether entry is approved or not. `0` for not approved, `Approved` for approved.
	 * @param int $form_id ID of the form of the entry being updated. Improves query performance.
	 * @param string $approvedcolumn Gravity Forms Field ID
	 *
	 * @return true|WP_Error
	 */
	private static function update_approved_column( $entry_id = 0, $status = '0', $form_id = 0, $approvedcolumn = 0 ) {

		if( empty( $approvedcolumn ) ) {
			$approvedcolumn = self::get_approved_column( $form_id );
		}

		if ( empty( $approvedcolumn ) ) {
			return true;
		}

		if ( ! GravityView_Entry_Approval_Status::is_valid( $status ) ) {
			return new WP_Error( 'invalid_status', 'Invalid entry approval status', $status );
		}

		//get the entry
		$entry = GFAPI::get_entry( $entry_id );

		//update entry
		$entry[ (string)$approvedcolumn ] = $status;

		/** @var bool|WP_Error $result */
		$result = GFAPI::update_entry( $entry );

		return $result;
	}

	/**
	 * Update the `is_approved` entry meta value
	 *
	 * @since 1.7.6.1 `after_update_entry_update_approved_meta` was previously to be named `update_approved_meta`
	 * @since 1.17.1 Added $form_id parameter
	 *
	 * @param  int $entry_id ID of the Gravity Forms entry
	 * @param  string $status String whether entry is approved or not. `0` for not approved, `Approved` for approved.
	 * @param int $form_id ID of the form of the entry being updated. Improves query performance.
	 *
	 * @return void
	 */
	private static function update_approved_meta( $entry_id, $status, $form_id = 0 ) {

		if ( ! GravityView_Entry_Approval_Status::is_valid( $status ) ) {
			do_action('gravityview_log_error', __METHOD__ . ': $is_approved not valid value', $status );
			return;
		}

		/**
		 * Make sure that the "User Opt-in" and the Admin Approve/Reject entry set the same meta value
		 * @since 1.16.6
		 */
		$is_approved = GravityView_Entry_Approval_Status::is_approved( $status );

		$approval_status = $is_approved ? GravityView_Entry_Approval_Status::Approved : GravityView_Entry_Approval_Status::Disapproved;

		// update entry meta
		if( function_exists('gform_update_meta') ) {

			gform_update_meta( $entry_id, self::meta_key, $approval_status, $form_id );

			/**
			 * @action `gravityview/approve_entries/updated` Triggered when an entry approval is updated
			 * @since 1.7.6.1
			 * @param  int $entry_id ID of the Gravity Forms entry
			 * @param  string|int $approval_status String whether entry is approved or not. `0` for not approved, `Approved` for approved.
			 */
			do_action( 'gravityview/approve_entries/updated', $entry_id, $approval_status );

			if( ! $is_approved ) {

				/**
				 * @action `gravityview/approve_entries/disapproved` Triggered when an entry is rejected
				 * @since 1.7.6.1
				 * @param  int $entry_id ID of the Gravity Forms entry
				 */
				do_action( 'gravityview/approve_entries/disapproved', $entry_id );

			} else {

				/**
				 * @action `gravityview/approve_entries/approved` Triggered when an entry is approved
				 * @since 1.7.6.1
				 * @param  int $entry_id ID of the Gravity Forms entry
				 */
				do_action( 'gravityview/approve_entries/approved', $entry_id );

			}

		} else {

			do_action('gravityview_log_error', __METHOD__ . ' - `gform_update_meta` does not exist.' );

		}
	}

	/**
	 * Calculate the approve field.input id
	 *
	 * @access public
	 * @static
	 * @param mixed $form GF Form or Form ID
	 * @return false|null|string Returns the input ID of the approved field. Returns NULL if no approved fields were found. Returns false if $form_id wasn't set.
	 */
	static public function get_approved_column( $form ) {

		if( empty( $form ) ) {
			return null;
		}

		if( !is_array( $form ) ) {
			$form = GVCommon::get_form( $form );
		}

		foreach( $form['fields'] as $key => $field ) {

			$field = (array) $field;

			if( !empty( $field['gravityview_approved'] ) ) {
				if( !empty($field['inputs'][0]['id']) ) {
					return $field['inputs'][0]['id'];
				}
			}

			// Note: This is just for backward compatibility from GF Directory plugin and old GV versions - when using i18n it may not work..
			if( 'checkbox' == $field['type'] && isset( $field['inputs'] ) && is_array( $field['inputs'] ) ) {
				foreach ( $field['inputs'] as $key2 => $input ) {
					if ( strtolower( $input['label'] ) == 'approved' ) {
						return $input['id'];
					}
				}
			}
		}

		return null;
	}

}

new GravityView_Entry_Approval;