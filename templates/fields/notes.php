<?php
/**
 * Display the name field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

require_once( GFCommon::get_base_path() . '/entry_detail.php' );

$gravityview_view = GravityView_View::getInstance();

$is_editable = $gravityview_view->getCurrentFieldSetting( 'notes_is_editable', false );

extract( $gravityview_view->getCurrentField() );

$notes = GravityView_Entry_Notes::get_notes( $entry['id'] );

wp_enqueue_style('gravityview-entry-notes', plugins_url( 'templates/css/entry-notes.css', GRAVITYVIEW_FILE ) );

?>
<form method="post" class="gv-entry-notes-form">
	<?php wp_nonce_field( 'gforms_update_note', 'gforms_update_note' ) ?>
	<div class="inside">
		<?php

		//getting email values
		$email_fields = GFCommon::get_email_fields( $form );
		$emails = array();
		$subject = '';

		foreach ( $email_fields as $email_field ) {
			if ( ! empty( $entry[ $email_field->id ] ) ) {
				$emails[] = $entry[ $email_field->id ];
			}
		}

		if ( sizeof( $notes ) > 0 && $is_editable && GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
			?>
			<div class="alignleft actions" style="padding:3px 0;">
				<label class="hidden" for="bulk_action"><?php esc_html_e( 'Bulk action', 'gravityforms' ) ?></label>
				<select name="bulk_action" id="bulk_action">
					<option value=''><?php esc_html_e( 'Bulk action ', 'gravityforms' ) ?></option>
					<option value='delete'><?php esc_html_e( 'Delete', 'gravityforms' ) ?></option>
				</select>
				<?php
				$apply_button = '<input type="submit" class="button" value="' . esc_attr__( 'Apply', 'gravityforms' ) . '" onclick="jQuery(\'#action\').val(\'bulk\');" style="width: 50px;" />';
				/**
				 * A filter to allow you to modify the note apply button
				 *
				 * @param string $apply_button The Apply Button HTML
				 */
				echo apply_filters( 'gform_notes_apply_button', $apply_button );
				?>
			</div>
			<?php
		}
		?>
		<table class="widefat fixed entry-detail-notes">
			<tbody id="the-comment-list" class="list:comment">
			<?php
				foreach ( $notes as $note ) {
					echo GravityView_Field_Notes::display_note( $note, $is_editable );
				}

				if ( $is_editable && GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
					echo GravityView_Field_Notes::add_note_field();
				}
			?>
			</tbody>
		</table>
	</div>
</form>