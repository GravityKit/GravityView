<?php
/**
 * @package     GravityView
 * @license     GPL2+
 * @since       1.15
 * @author      Katz Web Services, Inc.
 * @link        http://www.gravitykit.com
 * @copyright   Copyright 2016, Katz Web Services, Inc.
 */

/**
 * Class GravityView_Entry_Notes
 *
 * @since 1.15
 */
class GravityView_Entry_Notes {

	/**
	 * GravityView_Entry_Notes constructor.
	 */
	public function __construct() {
		$this->add_hooks();
	}

	/**
	 * @since 1.15
	 */
	private function add_hooks() {
		add_filter( 'gform_notes_avatar', array( 'GravityView_Entry_Notes', 'filter_avatar' ), 10, 2 );
	}


	/**
	 * Alias for GFFormsModel::add_note() with default note_type of 'gravityview'
	 *
	 * @see GFFormsModel::add_note()
	 *
	 * @since 1.15
	 * @since 1.17 Added return value
	 *
	 * @param int    $lead_id ID of the Entry
	 * @param int    $user_id ID of the user creating the note
	 * @param string $user_name User name of the user creating the note
	 * @param string $note Note content.
	 * @param string $note_type Type of note. Default: `gravityview`
	 *
	 * @return int|WP_Error Note ID, if success. WP_Error with $wpdb->last_error message, if failed.
	 */
	public static function add_note( $lead_id, $user_id, $user_name, $note = '', $note_type = 'gravityview' ) {
		global $wpdb;

		$default_note = array(
			'lead_id'   => 0,
			'user_id'   => 0,
			'user_name' => '',
			'note'      => '',
			'note_type' => 'gravityview',
		);

		/**
		 * Modify note values before added using GFFormsModel::add_note().
		 *
		 * @see GFFormsModel::add_note
		 * @since 1.15.2
		 * @param array $note Array with `lead_id`, `user_id`, `user_name`, `note`, and `note_type` key value pairs
		 */
		$note = apply_filters( 'gravityview/entry_notes/add_note', compact( 'lead_id', 'user_id', 'user_name', 'note', 'note_type' ) );

		// Make sure the keys are all set
		$note = wp_parse_args( $note, $default_note );

		$entry_id = (int) $note['lead_id'];
		$user_id  = (int) $note['user_id'];
		$user_name = esc_attr( $note['user_name'] );
		$note_content = $note['note'];
		$note_type = esc_attr( $note['note_type'] );

		// Call directly instead of through GFAPI::add_note() alias.
		GFFormsModel::add_note( $entry_id, $user_id, $user_name, $note_content, $note_type );

		// If last_error is empty string, there was no error.
		if ( empty( $wpdb->last_error ) ) {
			$return = $wpdb->insert_id;
		} else {
			$return = new WP_Error( 'gravityview-add-note', $wpdb->last_error );
		}

		return $return;
	}

	/**
	 * Alias for GFFormsModel::delete_note()
	 *
	 * @see GFFormsModel::delete_note()
	 * @param int $note_id Entry note ID
	 */
	public static function delete_note( $note_id ) {
		GFFormsModel::delete_note( $note_id );
	}

	/**
	 * Delete an array of notes
	 * Alias for GFFormsModel::delete_notes()
	 *
	 * @todo Write more efficient delete note method using SQL
	 * @param int[] $note_ids Array of entry note ids
	 */
	public static function delete_notes( $note_ids = array() ) {

		if ( ! is_array( $note_ids ) ) {

			gravityview()->log->error( 'Note IDs not an array. Not processing delete request.', array( 'data' => $note_ids ) );

			return;
		}

		GFFormsModel::delete_notes( $note_ids );
	}

	/**
	 * Alias for GFFormsModel::get_lead_notes()
	 *
	 * @see GFFormsModel::get_lead_notes
	 * @param int $entry_id Entry to get notes for
	 *
	 * @return stdClass[]|null Integer-keyed array of note objects
	 */
	public static function get_notes( $entry_id ) {

		$notes = GFFormsModel::get_lead_notes( $entry_id );

		/**
		 * Modify the notes array for an entry.
		 *
		 * @since 1.15
		 * @param stdClass[]|null $notes Integer-keyed array of note objects
		 * @param int $entry_id Entry to get notes for
		 */
		$notes = apply_filters( 'gravityview/entry_notes/get_notes', $notes, $entry_id );

		return $notes;
	}

	/**
	 * Get a single note by note ID
	 *
	 * @since 1.17
	 * @since TODO Deprecated in favor of GFAPI::get_note()
	 *
	 * @deprecated TODO
	 *
	 * @param int $note_id The ID of the note in the `{prefix}_rg_lead_notes` table
	 *
	 * @return object|false False if not found; note object otherwise.
	 */
	public static function get_note( $note_id ) {

		_deprecated_function( __METHOD__, 'TODO', 'GFAPI::get_note()' );

		$note = GFAPI::get_note( $note_id );

		if ( is_wp_error( $note ) ) {
			return false;
		}

		return $note;
	}

	/**
	 * Use the GravityView avatar for notes created by GravityView
	 * Note: The function is static so that it's easier to remove the filter: `remove_filter( 'gform_notes_avatar', array( 'GravityView_Entry_Notes', 'filter_avatar' ) );`
	 *
	 * @since 1.15
	 * @param string $avatar Avatar image, if available. 48px x 48px by default.
	 * @param object $note Note object with id, user_id, date_created, value, note_type, user_name, user_email vars.
	 * @return string Possibly-modified avatar.
	 */
	public static function filter_avatar( $avatar = '', $note = null ) {

		if ( 'gravityview' === $note->note_type && -1 === (int) $note->user_id ) {
			$avatar = sprintf( '<img src="%s" width="48" height="48" alt="GravityView" class="avatar avatar-48 gravityview-avatar" />', esc_url_raw( plugins_url( 'assets/images/floaty-avatar.png', GRAVITYVIEW_FILE ) ) );
		}

		return $avatar;
	}
}

new GravityView_Entry_Notes();
