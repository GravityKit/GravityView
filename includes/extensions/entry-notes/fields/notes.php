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

// TODO: Pass as global
$notes = GravityView_Entry_Notes::get_notes( $entry['id'] );
$has_notes_class = sizeof( $notes ) > 0 ? ' gv-has-notes' : ' gv-no-notes';

wp_enqueue_style( 'gravityview-entry-notes', plugins_url( '/assets/css/entry-notes.css', GravityView_Field_Notes::$file ) );
wp_enqueue_script( 'gravityview-entry-notes', plugins_url( '/assets/js/entry-notes.js', GravityView_Field_Notes::$file ) );

$entry_slug = gravityview_is_single_entry();
?>
<div class="gv-entry-notes<?php echo $has_notes_class; ?>">
	<form method="post" class="gv-entry-notes-list">
		<?php wp_nonce_field( 'gv_delete_notes_' . $entry_slug, 'gv_delete_notes' ) ?>
		<div class="inside">
			<input type="hidden" name="action" value="gv_delete_notes" />
			<input type="hidden" name="entry-slug" value="<?php echo esc_attr( $entry_slug ); ?>" />
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
			if ( $is_editable && GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
			?>
			<div class="gv-entry-notes-bulk-action">
				<label class="hidden" for="bulk_action"><?php esc_html_e( 'Bulk action', 'gravityforms' ) ?></label>
				<select name="bulk_action" id="bulk_action">
					<option value=''><?php esc_html_e( 'Bulk action ', 'gravityforms' ) ?></option>
					<option value='delete'><?php esc_html_e( 'Delete', 'gravityforms' ) ?></option>
				</select>
				<button type="submit" class="button button-small"><?php esc_html_e( 'Apply', 'gravityforms' ); ?></button>
			</div>
			<?php } ?>
			<table class="widefat fixed entry-detail-notes">
				<caption><?php esc_html_e('Notes for this entry'); ?></caption>
				<?php
				if ( $is_editable && GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
				?>
				<thead>
					<tr>
						<th scope="col" class="check-column">
							<label><input type="checkbox" value="" class="gv-notes-toggle"><span class="screen-reader-text"><?php esc_html_e( 'Toggle all notes', 'gravityview' ); ?></span></label>
						</th>
						<th scope="col" class="entry-detail-note" aria-label="<?php esc_html_e( 'Note Content', 'gravityview' ); ?>"></th>
					</tr>
				</thead>
				<?php } ?>
				<tbody id="the-comment-list" class="list:comment">
					<tr class="gv-entry-notes-no-notes"><td colspan="2"><?php
							// TODO: Filter
							esc_html_e( 'There are no notes.', 'gravityview' );
					?></td></tr>
				<?php
					foreach ( $notes as $note ) {
						echo GravityView_Field_Notes::display_note( $note, $is_editable );
					}
				?>
				</tbody>
			</table>
		</div>
	</form>

	<form method="post" class="gv-entry-note-add">
		<div>
			<input type="hidden" name="action" value="gv_add_note" />
			<input type="hidden" name="entry-slug" value="<?php echo esc_attr( $entry_slug ); ?>" />
			<?php
			wp_nonce_field( 'gv_add_note_' . $entry_slug, 'gv_add_note' );
			if ( $is_editable && GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
				echo GravityView_Field_Notes::add_note_field();
			}
			?>
		</div>
	</form>
</div>