<?php

/**
 * Add custom options for date fields
 */
class GravityView_Field_Notes extends GravityView_Field {

	var $name = 'notes';

	function __construct() {
		add_action( 'wp', array( $this, 'trigger_update_notes'), 1000 );

		parent::__construct();
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		unset( $field_options['show_as_link'] );

		$field_options['notes_is_editable'] = array(
			'type' => 'checkbox',
			'label' => __( 'Allow adding and deleting notes?', 'gravityview' ),
			//'desc' => __('Enable adding and deleting notes?', 'gravityview'),
			'value' => false,
		);
		return $field_options;
	}

	function trigger_update_notes() {

		if( rgpost('gforms_update_note') ) {

			check_admin_referer( 'gforms_update_note', 'gforms_update_note' );

			if( rgpost('bulk_action') ) {
				$this->delete_notes();
			} else {
				$this->add_note();
			}

		}
	}

	function delete_notes() {
		if ( $_POST['bulk_action'] == 'delete' ) {
			if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
				die( esc_html__( "You don't have adequate permission to delete notes.", 'gravityforms' ) );
			}
			RGFormsModel::delete_notes( $_POST['note'] );
		}
	}

	function add_note() {
		global $current_user;

		// Get entry from URL
		$entry = gravityview_is_single_entry();

		$lead = GFAPI::get_entry( $entry );
		$form = GFAPI::get_form( $lead['form_id'] );

		$user_data = get_userdata( $current_user->ID );
		RGFormsModel::add_note( $lead['id'], $current_user->ID, $user_data->display_name, stripslashes( $_POST['new_note'] ) );

		//emailing notes if configured
		if ( rgpost( 'gentry_email_notes_to' ) ) {
			GFCommon::log_debug( 'GFEntryDetail::lead_detail_page(): Preparing to email entry notes.' );
			$email_to      = $_POST['gentry_email_notes_to'];
			$email_from    = $current_user->user_email;
			$email_subject = stripslashes( $_POST['gentry_email_subject'] );
			$body = stripslashes( $_POST['new_note'] );

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
