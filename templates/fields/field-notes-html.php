<?php
/**
 * The default notes field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$entry          = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

if ( ! class_exists( 'GravityView_Entry_Notes' ) ) {
	return;
}

$visibility_settings   = empty( $field_settings['notes'] ) ? array() : $field_settings['notes'];
$show_notes_logged_out = ( ! empty( $visibility_settings['view'] ) && ! empty( $visibility_settings['view_loggedout'] ) );

if ( ! GVCommon::has_cap( array( 'gravityview_view_entry_notes', 'gravityview_add_entry_notes', 'gravityview_delete_entry_notes' ) ) && ! $show_notes_logged_out ) {
	return;
}

require_once GFCommon::get_base_path() . '/entry_detail.php';

/**
 * Print scripts and styles required for the Notes field.
 *
 * @since 1.17
 * @since 2.0 Changed parameter to \GV\Template_Context.
 *
 * @see GravityView_Field_Notes::enqueue_scripts
 *
 * @param \GV\Template_Context $gravityview The template context.
 */
do_action( 'gravityview/field/notes/scripts', $gravityview );

$notes      = (array) GravityView_Entry_Notes::get_notes( $entry['id'] );
$strings    = GravityView_Field_Notes::strings();
$entry_slug = GravityView_API::get_entry_slug( $entry['id'], $entry );

$show_add    = ! empty( $visibility_settings['add'] );
$show_delete = ( ! empty( $visibility_settings['delete'] ) && GVCommon::has_cap( 'gravityview_delete_entry_notes' ) );
$show_notes  = $show_notes_logged_out || ( ! empty( $visibility_settings['view'] ) && GVCommon::has_cap( 'gravityview_view_entry_notes' ) );

$container_class  = ( sizeof( $notes ) > 0 ? 'gv-has-notes' : 'gv-no-notes' );
$container_class .= $show_notes ? ' gv-show-notes' : ' gv-hide-notes';
?>
<div class="gv-notes <?php echo $container_class; ?>">
<?php
if ( $show_notes ) {
	?>
	<form method="post" class="gv-notes-list">
	<?php
	if ( $show_delete ) {
		wp_nonce_field( 'gv_delete_notes_' . $entry_slug, 'gv_delete_notes' ); }
	?>
		<div>
			<input type="hidden" name="action" value="gv_delete_notes" />
			<input type="hidden" name="entry-slug" value="<?php echo esc_attr( $entry_slug ); ?>" />
			<table>
				<caption><?php echo $strings['caption']; ?></caption>
			<?php
			if ( $show_delete ) {
				?>
				<thead>
					<tr>
						<th colspan="2">
							<label><input type="checkbox" value="" class="gv-notes-toggle" title="<?php echo $strings['toggle-notes']; ?>"><span class="screen-reader-text"><?php echo $strings['toggle-notes']; ?></span></label>
							<button type="submit" class="button button-small gv-notes-delete"><?php echo $strings['delete']; ?></button>
						</th>
					</tr>
				</thead>
				<?php } ?>
				<tbody>
					<tr class="gv-notes-no-notes"><td colspan="2"><?php echo $strings['no-notes']; ?></td></tr>
				<?php
				foreach ( $notes as $note ) {
					echo GravityView_Field_Notes::display_note( $note, $show_delete, $gravityview );
				}
				?>
				</tbody>
			</table>
		</div>
	</form>
	<?php
} // End if can view notes

if ( $show_add ) {
	echo GravityView_Field_Notes::get_add_note_part( array(), $gravityview );
}
?>
</div>
