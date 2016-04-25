<?php
/**
 * Display the name field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

require_once( GFCommon::get_base_path() . '/entry_detail.php' );

$gravityview_view = GravityView_View::getInstance();

$is_editable = $gravityview_view->getCurrentFieldSetting( 'notes_is_editable' );

extract( $gravityview_view->getCurrentField() );

// TODO: Pass as global
$notes = GravityView_Entry_Notes::get_notes( $entry['id'] );

$strings = GravityView_Field_Notes::strings();

wp_enqueue_style( 'gravityview-entry-notes', plugins_url( '/assets/css/entry-notes.css', GravityView_Field_Notes::$file ) );
wp_enqueue_script( 'gravityview-entry-notes', plugins_url( '/assets/js/entry-notes.js', GravityView_Field_Notes::$file ) );

if( ! wp_script_is( 'gravityview-entry-notes', 'done' ) ) {
	wp_localize_script( 'gravityview-entry-notes', 'GVEntryNotes', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'text' => array(
			'processing' => $strings['processing'],
		),
	) );
}

$entry_slug = gravityview_is_single_entry();
?>
<div class="gv-entry-notes<?php echo ( sizeof( $notes ) > 0 ? ' gv-has-notes' : ' gv-no-notes' ); ?>">
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
				<label class="screen-reader-text" for="gv-entry-notes-bulk-action-<?php echo esc_attr( $entry_slug ); ?>"><?php echo $strings['bulk-action']; ?></label>
				<select name="entry-notes-bulk-action" id="gv-entry-notes-bulk-action-<?php echo esc_attr( $entry_slug ); ?>">
					<option value=''><?php echo $strings['bulk-action']; ?></option>
					<option value='delete'><?php echo $strings['delete']; ?></option>
				</select>
				<button type="submit" class="button button-small"><?php echo $strings['bulk-action-button']; ?></button>
			</div>
			<?php } ?>
			<table>
				<caption><?php echo $strings['caption']; ?></caption>
				<?php
				if ( $is_editable && GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
				?>
				<thead>
					<tr>
						<th scope="col" class="check-column">
							<label><input type="checkbox" value="" class="gv-notes-toggle"><span class="screen-reader-text"><?php echo $strings['toggle-notes']; ?></span></label>
						</th>
						<th scope="col" class="entry-detail-note" aria-label="<?php echo $strings['note-content-column']; ?>"></th>
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

	<?php if( $is_editable ) { echo do_shortcode( '[gv_note_add]' ); } ?>

</div>