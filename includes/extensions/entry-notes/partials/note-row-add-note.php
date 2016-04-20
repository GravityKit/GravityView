<?php

$note_emails = (array) $note_emails;

if ( sizeof( $note_emails ) > 0 ) { ?>
		<div>
			<select name="gv_entry_email_notes_to" onchange="if(jQuery(this).val() != '')
			{jQuery('.gv-entry-note-email-subject-container').css('display', 'inline');}
			else{jQuery('.gv-entry-note-email-subject-container').css('display', 'none');}">
				<option value=""><?php esc_html_e( 'Also email this note to', 'gravityforms' ) ?></option>
				<?php foreach ( $note_emails as $email ) { ?>
					<option value="<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></option>
				<?php } ?>
			</select>


            <span class='gv-entry-note-email-subject-container'>
                <label for="gentry_email_subject"><?php esc_html_e( 'Subject:', 'gravityforms' ) ?></label>
                <input type="text" name="gentry_email_subject" id="gentry_email_subject" value="" style="width:35%"/>
            </span>
		</div>
<?php } ?>

<textarea name="new_note" id="gv-entry-note-content"></textarea>

<button type="submit" class="button gv-add-note-submit"><?php esc_attr_e( 'Add Note', 'gravityforms' ); ?></button>