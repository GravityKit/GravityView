<?php
/**
 * Display the name field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

$visibility_settings = $gravityview_view->getCurrentFieldSetting( 'notes' );
$show_notes_logged_out = ( ! empty( $visibility_settings['view'] ) && ! empty( $visibility_settings['view_loggedout'] ) );

if(	! GVCommon::has_cap( array( 'gravityview_view_entry_notes', 'gravityview_add_entry_notes', 'gravityview_delete_entry_notes' ) ) && ! $show_notes_logged_out ) {
	return;
}

require_once( GFCommon::get_base_path() . '/entry_detail.php' );

/**
 * @action `gravityview/field/notes/scripts` Print scripts and styles required for the Notes field
 * @see GravityView_Field_Notes::enqueue_scripts
 * @since 1.17
 */
do_action( 'gravityview/field/notes/scripts' );

$entry = $gravityview_view->getCurrentEntry();
$notes = GravityView_Entry_Notes::get_notes( $entry['id'] );
$strings = GravityView_Field_Notes::strings();
$entry_slug = GravityView_API::get_entry_slug( $entry['id'], $entry );

$show_add = ! empty( $visibility_settings['add'] );
$show_delete = ( ! empty( $visibility_settings['delete'] ) && GVCommon::has_cap( 'gravityview_delete_entry_notes' ) );
$show_notes = $show_notes_logged_out || ( ! empty( $visibility_settings['view'] ) && GVCommon::has_cap( 'gravityview_view_entry_notes' ) );

$container_class = ( sizeof( $notes ) > 0 ? 'gv-has-notes' : 'gv-no-notes' );
$container_class .= $show_notes ? ' gv-show-notes' : ' gv-hide-notes';
?>
<div class="gv-notes <?php echo $container_class; ?>">
<?php
	if( $show_notes ) {
?>
	<form method="post" class="gv-notes-list">
		<?php if ( $show_delete ) { wp_nonce_field( 'gv_delete_notes_' . $entry_slug, 'gv_delete_notes' ); } ?>
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
							echo GravityView_Field_Notes::display_note( $note, $show_delete );
						}
					?>
				</tbody>
			</table>
		</div>
	</form>
<?php
	} // End if can view notes

	if( $show_add ) {
		echo do_shortcode( '[gv_note_add]' );
	}
?>
</div>