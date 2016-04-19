<?php

/**
 * Add custom options for date fields
 */
class GravityView_Field_Notes extends GravityView_Field {

	/**
	 * @var string
	 */
	static $file;

	/**
	 * @var string
	 */
	static $path;

	var $name = 'notes';

	function __construct() {

		self::$path = plugin_dir_path( __FILE__ );
		self::$file = __FILE__;

		$this->add_hooks();

		parent::__construct();
	}

	private function add_hooks() {


		add_action( 'wp', array( $this, 'process_delete_notes'), 1000 );
		add_action( 'wp_ajax_nopriv_gv_delete_notes', array( $this, 'process_delete_notes') );
		add_action( 'wp_ajax_gv_delete_notes', array( $this, 'process_delete_notes') );

		add_action( 'wp', array( $this, 'process_add_note'), 1000 );
		add_action( 'wp_ajax_nopriv_gv_add_note', array( $this, 'process_add_note') );
		add_action( 'wp_ajax_gv_add_note', array( $this, 'process_add_note') );

		// add template path to check for field
		add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );
	}

	function process_add_note() {

		if( ! GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
			return;
		}

		if( isset( $_POST['action'] ) && 'gv_add_note' === $_POST['action'] ) {

			parse_str( wp_unslash( $_POST['data'] ), $data );

			$valid = wp_verify_nonce( $data['gv_add_note'], 'gv_add_note_' . $data['entry-slug'] );

			if( $valid ) {
				$entry = gravityview_get_entry( $data['entry-slug'], false );

				$added = $this->add_note( $entry, $data );

				if( is_wp_error( $added ) ) {
					wp_send_json_error( array( 'message' => $added->get_error_message() ) );
				} else {
					$note = $this->get_note( $added );
					$html = self::display_note( $note, true );
					wp_send_json_success( array( 'html' => $html ) );
				}
			}
		}
	}

	function process_delete_notes() {

		if( ! GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
			return;
		}

		if( isset( $_POST['action'] ) && 'gv_delete_notes' === $_POST['action'] ) {

			parse_str( wp_unslash( $_POST['data'] ), $data );

			$data = wp_parse_args( $data, array( 'gv_delete_notes' => '', 'entry-slug' => '' ) );

			$valid = wp_verify_nonce( $data['gv_delete_notes'], 'gv_delete_notes_' . $data['entry-slug'] );

			if( $valid ) {
				$this->delete_notes( $data['note'] );
				wp_send_json_success();
			} else {
				wp_send_json_error( array( 'message' => new WP_Error('The request was invalid.' ) ) );
			}
		}

	}

	/**
	 * Include this extension templates path
	 * @param array $file_paths List of template paths ordered
	 */
	public function add_template_path( $file_paths ) {

		$file_paths[ 172 ] = self::$path;
		$file_paths[ 173 ] = self::$path . 'partials/';

		return $file_paths;
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		unset( $field_options['show_as_link'] );

		$field_options['notes_is_editable'] = array(
			'type' => 'checkbox',
			'label' => __( 'Allow adding and deleting notes?', 'gravityview' ),
			'value' => false,
		);

		$field_options['note_text_add_note'] = array(
			'type' => 'text',
			'label' => "Add Note button text",
			'value' => __('Add Note'),
		);

		return $field_options;
	}

	/**
	 * @param int[] $notes
	 */
	function delete_notes( $notes = array() ) {

		if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
			wp_die( esc_html__( "You don't have adequate permission to delete notes.", 'gravityforms' ) );
		}

		RGFormsModel::delete_notes( $notes );
	}

	static public function display_note( $note, $is_editable = false ) {

		$note_content = array(
			'avatar'                 => apply_filters( 'gform_notes_avatar', get_avatar( $note->user_id, 48 ), $note ),
			'user_name'              => $note->user_name,
			'user_email'             => $note->user_email,
			'added_on'               => __( 'added on {date_created_formatted}' ),
			'value'                  => nl2br( esc_html( $note->value ) ),
			'date_created'           => $note->date_created,
			'date_created_formatted' => GFCommon::format_date( $note->date_created, false ),
			'user_id'                => intval( $note->user_id ),
			'note_type'              => $note->note_type,
			'id'                     => intval( $note->id ),
		);

		ob_start();
		GravityView_View::getInstance()->get_template_part( 'note', 'detail' );
		$note_detail_html = ob_get_clean();

		foreach ( $note_content as $tag => $value ) {
			$note_detail_html = str_replace( '{' . $tag . '}', $value, $note_detail_html );
		}

#if ( $is_editable && GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {

		ob_start();
		GravityView_View::getInstance()->get_template_part( 'note', 'row-editable' );
		$note_row_editable = ob_get_clean();

		ob_start();
		GravityView_View::getInstance()->get_template_part( 'note', 'row' );
		$note_row = ob_get_clean();

		$replacements = array(
			'{note_id}' => $note_content['id'],
			'{row_class}' => 'gv-entry-note',
			'{note_detail}' => $note_detail_html
		);

		foreach ( $replacements as $tag => $replacement ) {
			$note_row_editable = str_replace( $tag, $replacement, $note_row_editable );
			$note_row = str_replace( $tag, $replacement, $note_row );
		}

		if ( $is_editable && GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
			return $note_row_editable;
		} else {
			return $note_row;
		}

	}

	function add_note( $entry, $data ) {
		global $current_user, $wpdb;

		// Get entry from URL
		$user_data = get_userdata( $current_user->ID );
		
		$note_content = wp_unslash( trim( $data['new_note'] ) );

		if( empty( $note_content ) ) {
			return new WP_Error( 'gv-add-note-empty', __( 'The note is empty.', 'gravityview' ) );
		}

		RGFormsModel::add_note( $entry['id'], $current_user->ID, $user_data->display_name, $note_content );

		if( empty( $wpdb->last_error ) ) {
			$return = $wpdb->insert_id;
		} else {
			$return = new WP_Error( 'gv-add-note', $wpdb->last_error );
		}

		$this->maybe_send_entry_notes( $entry, $data );

		return $return;
	}

	/**
	 * Get a single note by note ID
	 *
	 * @param int $note_id The ID of the note in the `rg_lead_notes` table
	 *
	 * @return object|bool False if not found; note object otherwise.
	 */
	private function get_note( $note_id ) {
		global $wpdb;

		$notes_table = GFFormsModel::get_lead_notes_table_name();

		$results = $wpdb->get_results(
			$wpdb->prepare(
				" SELECT n.id, n.user_id, n.date_created, n.value, n.note_type, ifnull(u.display_name,n.user_name) as user_name, u.user_email
	              FROM $notes_table n
	              LEFT OUTER JOIN $wpdb->users u ON n.user_id = u.id
	              WHERE n.id=%d", $note_id
			)
		);

		return $results ? $results[0] : false;
	}

	public static function add_note_field() {
		$gravityview_view = GravityView_View::getInstance();
		//getting email values
		$email_fields = GFCommon::get_email_fields( $gravityview_view->getForm() );
		$lead = $gravityview_view->getCurrentEntry();
		$emails = array();

		foreach ( $email_fields as $email_field ) {
			if ( ! empty( $lead[ $email_field->id ] ) ) {
				$emails[] = $lead[ $email_field->id ];
			}
		}

		ob_start();
		GravityView_View::getInstance()->set_template_data( $emails, 'note_emails' );
		GravityView_View::getInstance()->get_template_part( 'note', 'row-add-note' );
		return ob_get_clean();
	}

	function maybe_send_entry_notes( $entry, $data ) {
		//emailing notes if configured
		if ( rgpost( 'gv_entry_email_notes_to' ) ) {
			$current_user = wp_get_current_user();

			$form = GFAPI::get_form( $entry['form_id'] );

			GFCommon::log_debug( 'GFEntryDetail::lead_detail_page(): Preparing to email entry notes.' );
			$email_to      = $data['gv_entry_email_notes_to'];
			$email_from    = $current_user->user_email;
			$email_subject = stripslashes( $data['gentry_email_subject'] );
			$body = stripslashes( $data['new_note'] );

			$headers = "From: \"$email_from\" <$email_from> \r\n";
			GFCommon::log_debug( "GFEntryDetail::lead_detail_page(): Emailing notes - TO: $email_to SUBJECT: $email_subject BODY: $body HEADERS: $headers" );
			$is_success  = wp_mail( $email_to, $email_subject, $body, $headers );
			$result = is_wp_error( $is_success ) ? $is_success->get_error_message() : $is_success;
			GFCommon::log_debug( "GFEntryDetail::lead_detail_page(): Result from wp_mail(): {$result}" );
			if ( ! is_wp_error( $is_success ) && $is_success ) {
				GFCommon::log_debug( 'GFEntryDetail::lead_detail_page(): Mail was passed from WordPress to the mail server.' );
			} else {
				GFCommon::log_error( 'GFEntryDetail::lead_detail_page(): The mail message was passed off to WordPress for processing, but WordPress was unable to send the message.' );
			}

			if ( has_filter( 'phpmailer_init' ) ) {
				GFCommon::log_debug( __METHOD__ . '(): The WordPress phpmailer_init hook has been detected, usually used by SMTP plugins, it can impact mail delivery.' );
			}

			/**
			 * Fires after a note is attached to an entry and sent as an email
			 *
			 * @param string $result The Error message or success message when the entry note is sent
			 * @param string $email_to The email address to send the entry note to
			 * @param string $email_from The email address from which the email is sent from
			 * @param string $email_subject The subject of the email that is sent
			 * @param mixed $body The Full body of the email containing the message after the note is sent
			 * @param array $form The current form object
			 * @param array $lead The Current lead object
			 */
			do_action( 'gform_post_send_entry_note', $result, $email_to, $email_from, $email_subject, $body, $form, $lead );
		}
	}
}

new GravityView_Field_Notes;
