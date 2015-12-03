<?php

/**
 * Class GravityView_Entry_Notes
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
	 * @see GFFormsModel::add_note()
	 * @since 1.15
	 * @param int $lead_id ID of the Entry
	 * @param int $user_id ID of the user creating the note
	 * @param string $user_name User name of the user creating the note
	 * @param string $note Note content.
	 * @param string $note_type Type of note. Default: `gravityview`
	 */
	public static function add_note( $lead_id, $user_id, $user_name, $note = '', $note_type = 'gravityview' ) {

		$default_note = array(
			'lead_id' => 0,
			'user_id' => 0,
			'user_name' => '',
			'note' => '',
			'note_type' => 'gravityview',
		);

		/**
		 * @filter `gravityview/entry_notes/add_note` Modify note values before added using GFFormsModel::add_note()
		 * @see GFFormsModel::add_note
		 * @since 1.15.2
		 * @param array $note Array with `lead_id`, `user_id`, `user_name`, `note`, and `note_type` key value pairs
		 */
		$note = apply_filters( 'gravityview/entry_notes/add_note', compact( 'lead_id', 'user_id', 'user_name', 'note', 'note_type' ) );

		// Make sure the keys are all set
		$note = wp_parse_args( $note, $default_note );

		GFFormsModel::add_note( intval( $note['lead_id'] ), intval( $note['user_id'] ), esc_attr( $note['user_name'] ), $note['note'], esc_attr( $note['note_type'] ) );
	}

	/**
	 * Alias for GFFormsModel::delete_note()
	 * @see GFFormsModel::delete_note()
	 * @param int $note_id Entry note ID
	 */
	public static function delete_note( $note_id ) {
		GFFormsModel::delete_note( $note_id );
	}

	/**
	 * Delete an array of notes
	 * Alias for GFFormsModel::delete_notes()
	 * @todo Write more efficient delete note method using SQL
	 * @param int[] $note_ids Array of entry note ids
	 */
	public static function delete_notes( $note_ids = array() ) {

		if( !is_array( $note_ids ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . ' - Note IDs not an array', $note_ids );
		}

		GFFormsModel::delete_notes( $note_ids );
	}

	/**
	 * Alias for GFFormsModel::get_lead_notes()
	 *
	 * @see GFFormsModel::get_lead_notes
	 * @param int $entry_id Entry to get notes for
	 *
	 * @return stdClass[] Integer-keyed array of note objects
	 */
	public static function get_notes( $entry_id ) {
		$notes = GFFormsModel::get_lead_notes( $entry_id );

		/**
		 * @filter `gravityview/entry_notes/get_notes` Modify the notes array for an entry
		 * @since 1.15
		 * @param stdClass[] $notes Integer-keyed array of note objects
		 * @param int $entry_id Entry to get notes for
		 */
		$notes = apply_filters( 'gravityview/entry_notes/get_notes', $notes, $entry_id );

		return $notes;
	}

	/**
	 * Use the GravityView avatar for notes created by GravityView
	 * Note: The function is static so that it's easier to remove the filter: `remove_filter( 'gform_notes_avatar', array( 'GravityView_Entry_Notes', 'filter_avatar' ) );`
	 * @since 1.15
	 * @param string $avatar Avatar image, if available. 48px x 48px by default.
	 * @param object $note Note object with id, user_id, date_created, value, note_type, user_name, user_email vars
	 * @return string Possibly-modified avatar
	 */
	public static function filter_avatar( $avatar = '', $note ) {

		if( 'gravityview' === $note->note_type && -1 === (int)$note->user_id ) {
			$avatar =  sprintf( '<img src="%s" width="48" height="48" alt="GravityView" class="avatar avatar-48 gravityview-avatar" />', esc_url_raw( plugins_url( 'assets/images/floaty-avatar.png', GRAVITYVIEW_FILE ) ) );
		}

		return $avatar;
	}

}

new GravityView_Entry_Notes;