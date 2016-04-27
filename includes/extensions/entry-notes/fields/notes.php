<?php
/**
 * Display the name field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

require_once( GFCommon::get_base_path() . '/entry_detail.php' );

if( ! GVCommon::has_cap( 'gravityview_view_entry_notes' ) ) {
	return;
}

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
			'delete_confirm' => $strings['delete-confirm'],
		),
	) );
}

$entry_slug = GravityView_API::get_entry_slug( $entry['id'], $entry );

$show_delete = ( $is_editable && GVCommon::has_cap( 'gravityview_delete_entry_notes' ) );

?>
<div class="gv-entry-notes<?php echo ( sizeof( $notes ) > 0 ? ' gv-has-notes' : ' gv-no-notes' ); ?>">
	<form method="post" class="gv-entry-notes-list">
		<?php wp_nonce_field( 'gv_delete_notes_' . $entry_slug, 'gv_delete_notes' ) ?>
		<div class="inside">
			<input type="hidden" name="action" value="gv_delete_notes" />
			<input type="hidden" name="entry-slug" value="<?php echo esc_attr( $entry_slug ); ?>" />
			<?php

			if ( $show_delete ) {
			?>
			<div class="gv-entry-notes-delete">
				<button type="submit" class="button button-small"><?php echo $strings['delete']; ?></button>
			</div>
			<?php } ?>
			<table>
				<caption><?php echo $strings['caption']; ?></caption>
				<?php
				if ( $show_delete ) {
				?>
				<thead>
					<tr>
						<th colspan="2">
							<label><input type="checkbox" value="" class="gv-notes-toggle" title="<?php echo $strings['toggle-notes']; ?>"><span class="screen-reader-text"><?php echo $strings['toggle-notes']; ?></span></label>
						</th>
					</tr>
				</thead>
				<?php } ?>
				<tbody>
					<tr class="gv-entry-notes-no-notes"><td colspan="2"><?php echo $strings['no-notes']; ?></td></tr>
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