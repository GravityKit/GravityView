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

	function trigger_update_notes() {

		if( rgpost('gforms_update_note') ) {

			$valid = wp_verify_nonce( rgpost('gforms_update_note'), 'gforms_update_note' );

			if( $valid ) {
				if ( rgpost( 'bulk_action' ) ) {
					$this->delete_notes();
				} else {
					$this->add_note();
				}
			}
		}
	}

	function delete_notes() {
		if ( $_POST['bulk_action'] == 'delete' ) {
			if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
				wp_die( esc_html__( "You don't have adequate permission to delete notes.", 'gravityforms' ) );
			}
			RGFormsModel::delete_notes( $_POST['note'] );
		}
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

		$note_detail_html = <<<EOD
<div>
	<div class="note-avatar">{avatar}</div>
	<h6 class="note-author">{user_name}</h6>
	<p class="note-email">
		<a href="mailto:{user_email}">{user_email}</a><br />
		{added_on}
	</p>
</div>
<div class="detail-note-content gforms_note_gravityview">{value}</div>
EOD;

		foreach ( $note_content as $tag => $value ) {
			$note_detail_html = str_replace( '{' . $tag . '}', $value, $note_detail_html );
		}

#if ( $is_editable && GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
		$note_row_editable = <<<EOD
<tr class="{row_class}">
	<th class="check-column" scope="row">
		<input type="checkbox" value="{note_id}" name="note[]" />
	</th>
	<td colspan="2" class="entry-detail-note">
		{note_detail}
	</td>
</tr>
EOD;

		$note_row = <<<EOD
<tr class="{row_class} {last_row_class}">
	<td class="entry-detail-note">
		{note_detail}
	</td>
</tr>
EOD;
		$note_row_editable = str_replace( '{note_id}', $note_content['id'], $note_row_editable );
		$note_row = str_replace( '{note_id}', $note_content['id'], $note_row );

		if ( $is_editable && GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {

			return str_replace( '{note_detail}', $note_detail_html, $note_row_editable );

		} else {
			return str_replace( '{note_detail}', $note_detail_html, $note_row );
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

		$this->maybe_send_entry_notes();
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
		?>
		<tr class="gv-entry-add-note-row">
			<td colspan="3">

				<?php

				if ( ! empty( $emails ) ) {
					?>
					&nbsp;&nbsp;
					<div>

						<select name=" gentry_email_notes_to" onchange="if(jQuery(this).val() != '')
				{jQuery('.gv-entry-note-email-subject-container').css('display', 'inline');}
				else{jQuery('.gv-entry-note-email-subject-container').css('display', 'none');}">
							<option value=""><?php esc_html_e( 'Also email this note to', 'gravityforms' ) ?></option>
							<?php foreach ( $emails as $email ) { ?>
								<option value="<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></option>
							<?php } ?>
						</select>


			            <span class='gv-entry-note-email-subject-container'>
			                <label for="gentry_email_subject"><?php esc_html_e( 'Subject:', 'gravityforms' ) ?></label>
			                <input type="text" name="gentry_email_subject" id="gentry_email_subject" value="" style="width:35%"/>
			            </span>
					</div>
				<?php } ?>

				<textarea name="new_note"></textarea>

				<input type="submit" name="add_note" value="<?php echo esc_attr__( 'Add Note', 'gravityforms' ); ?>" class="button gv-entry-add-note" onclick="jQuery('#action').val('add_note');" />

			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	function maybe_send_entry_notes() {
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
