<?php
/**
 * @file      class-gravityview-admin-bulk-actions.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      https://www.gravitykit.com
 * @copyright Copyright 2021, Katz Web Services, Inc.
 *
 * @since 2.13.2
 */

/**
 * Handles interactions between GravityView and Gravity Forms' Bulk Actions menu on the Entries screen.
 *
 * @since 2.13.2
 */
class GravityView_Bulk_Actions {

	// hold notification messages
	public static $bulk_update_message = '';

	/**
	 * @var array Set the prefixes here instead of spread across the class
	 */
	private static $bulk_action_prefixes = array(
		'approve'    => 'gvapprove',
		'disapprove' => 'gvdisapprove',
		'unapprove'  => 'gvunapprove',
	);

	/**
	 * GravityView_Admin_Bulk_Actions constructor.
	 */
	public function __construct() {

		if ( did_action( 'admin_init' ) ) {
			$this->process_bulk_action();
		} else {
			// capture bulk actions
			add_action( 'admin_init', array( $this, 'process_bulk_action' ) );
		}
	}

	/**
	 * Get the Bulk Action submitted value if it is a GravityView Approve/Unapprove action
	 *
	 * @since 1.17.1
	 * @since 2.13.2 Moved to GravityView_Bulk_Actions class.
	 *
	 * @return string|false If the bulk action was GravityView Approve/Unapprove, return the full string (gvapprove-16, gvunapprove-16). Otherwise, return false.
	 */
	private function get_gv_bulk_action() {

		$gv_bulk_action = false;

		if ( version_compare( GFForms::$version, '2.0', '>=' ) ) {
			$bulk_action = ( '-1' !== \GV\Utils::_POST( 'action' ) ) ? \GV\Utils::_POST( 'action' ) : \GV\Utils::_POST( 'action2' );
		} else {
			// GF 1.9.x - Bulk action 2 is the bottom bulk action select form.
			$bulk_action = \GV\Utils::_POST( 'bulk_action' ) ? \GV\Utils::_POST( 'bulk_action' ) : \GV\Utils::_POST( 'bulk_action2' );
		}

		// Check the $bulk_action value against GV actions, see if they're the same. I hate strpos().
		if ( ! empty( $bulk_action ) && preg_match( '/^(' . implode( '|', self::$bulk_action_prefixes ) . ')/ism', $bulk_action ) ) {
			$gv_bulk_action = $bulk_action;
		}

		return $gv_bulk_action;
	}

	/**
	 * Capture bulk actions - gf_entries table
	 *
	 * @uses  GravityView_frontend::get_search_criteria() Convert the $_POST search request into a properly formatted request.
	 * @return void|boolean
	 */
	public function process_bulk_action() {

		if ( ! is_admin() || ! class_exists( 'GFForms' ) || empty( $_POST ) ) {
			return false;
		}

		// The action is formatted like: gvapprove-16 or gvunapprove-16, where the first word is the name of the action and the second is the ID of the form.
		$bulk_action = $this->get_gv_bulk_action();

		// gforms_entry_list is the nonce that confirms we're on the right page
		// gforms_update_note is sent when bulk editing entry notes. We don't want to process then.
		if ( ! ( $bulk_action && \GV\Utils::_POST( 'gforms_entry_list' ) && empty( $_POST['gforms_update_note'] ) ) ) {
			return;
		}

		check_admin_referer( 'gforms_entry_list', 'gforms_entry_list' );

		/**
		 * The extra '-' is to make sure that there are at *least* two items in array.
		 *
		 * @see https://github.com/katzwebservices/GravityView/issues/370
		 */
		$bulk_action .= '-';

		list( $approved_status, $form_id ) = explode( '-', $bulk_action );

		if ( empty( $form_id ) ) {
			gravityview()->log->error( 'Form ID is empty from parsing bulk action.', array( 'data' => $bulk_action ) );
			return false;
		}

		// All entries are set to be updated, not just the visible ones
		if ( ! empty( $_POST['all_entries'] ) ) {

			// Convert the current entry search into GF-formatted search criteria
			$search = array(
				'search_field'    => isset( $_POST['f'] ) ? $_POST['f'][0] : 0,
				'search_value'    => isset( $_POST['v'][0] ) ? $_POST['v'][0] : '',
				'search_operator' => isset( $_POST['o'][0] ) ? $_POST['o'][0] : 'contains',
			);

			$search_criteria = GravityView_frontend::get_search_criteria( $search, $form_id );

			// Make sure the entry list class is loaded.
			require_once GFCommon::get_base_path() . '/entry_list.php';

			$GF_Entry_List_Table = new GF_Entry_List_Table();

			$search_criteria = $GF_Entry_List_Table->get_search_criteria( $search_criteria );

			// Get all the entry IDs for the form
			$entries = GVCommon::get_entry_ids( $form_id, $search_criteria );

		} else {

			// Changed from 'lead' to 'entry' in 2.0
			$entries = isset( $_POST['lead'] ) ? $_POST['lead'] : $_POST['entry'];

		}

		if ( empty( $entries ) ) {
			gravityview()->log->error( 'Entries are empty' );
			return false;
		}

		$entry_count = count( $entries ) > 1 ? sprintf( __( '%d entries', 'gk-gravityview' ), count( $entries ) ) : __( '1 entry', 'gk-gravityview' );

		switch ( $approved_status ) {
			case self::$bulk_action_prefixes['approve']:
				GravityView_Entry_Approval::update_bulk( $entries, GravityView_Entry_Approval_Status::APPROVED, $form_id );
				self::$bulk_update_message = sprintf( __( '%s approved.', 'gk-gravityview' ), $entry_count );
				break;
			case self::$bulk_action_prefixes['unapprove']:
				GravityView_Entry_Approval::update_bulk( $entries, GravityView_Entry_Approval_Status::UNAPPROVED, $form_id );
				self::$bulk_update_message = sprintf( __( '%s unapproved.', 'gk-gravityview' ), $entry_count );
				break;
			case self::$bulk_action_prefixes['disapprove']:
				GravityView_Entry_Approval::update_bulk( $entries, GravityView_Entry_Approval_Status::DISAPPROVED, $form_id );
				self::$bulk_update_message = sprintf( __( '%s disapproved.', 'gk-gravityview' ), $entry_count );
				break;
		}
	}


	/**
	 * Get an array of options to be added to the Gravity Forms "Bulk action" dropdown in a "GravityView" option group
	 *
	 * @since 1.16.3
	 *
	 * @param int $form_id  ID of the form currently being displayed
	 *
	 * @return array Array of actions to be added to the GravityView option group
	 */
	public static function get_bulk_actions( $form_id ) {

		$bulk_actions = array(
			'GravityView' => array(
				array(
					'label' => GravityView_Entry_Approval_Status::get_string( 'approved', 'action' ),
					'value' => sprintf( '%s-%d', self::$bulk_action_prefixes['approve'], $form_id ),
				),
				array(
					'label' => GravityView_Entry_Approval_Status::get_string( 'disapproved', 'action' ),
					'value' => sprintf( '%s-%d', self::$bulk_action_prefixes['disapprove'], $form_id ),
				),
				array(
					'label' => GravityView_Entry_Approval_Status::get_string( 'unapproved', 'action' ),
					'value' => sprintf( '%s-%d', self::$bulk_action_prefixes['unapprove'], $form_id ),
				),
			),
		);

		/**
		 * Modify the GravityView "Bulk action" dropdown list. Return an empty array to hide.
		 *
		 * @see https://gist.github.com/zackkatz/82785402c996b51b4dc9 for an example of how to use this filter
		 *
		 * @since 1.16.3
		 *
		 * @param array $bulk_actions Associative array of actions to be added to "Bulk action" dropdown inside GravityView `<optgroup>`. Parent array key is the `<optgroup>` label, then each child array must have `label` (displayed text) and `value` (input value) keys
		 * @param int   $form_id      ID of the form currently being displayed.
		 */
		$bulk_actions = apply_filters( 'gravityview/approve_entries/bulk_actions', $bulk_actions, $form_id );

		// Sanitize the values, just to be sure.
		foreach ( $bulk_actions as $key => $group ) {

			if ( empty( $group ) ) {
				continue;
			}

			foreach ( $group as $i => $action ) {
				$bulk_actions[ $key ][ $i ]['label'] = esc_html( $bulk_actions[ $key ][ $i ]['label'] );
				$bulk_actions[ $key ][ $i ]['value'] = esc_attr( $bulk_actions[ $key ][ $i ]['value'] );
			}
		}

		return $bulk_actions;
	}
}

new GravityView_Bulk_Actions();
