<?php
/**
 * Display a note, without editing options
 *
 * @since 1.17
 */
?>
<form method="post" class="gv-entry-note-add">
	<div>
		<input type="hidden" name="action" value="gv_note_add" />
		<input type="hidden" name="entry-slug" value="{entry_slug}" />
		{nonce_field}
		{emails_dropdown}

		<label for="gv-entry-note-content" class="screen-reader-text"><?php esc_html_e( 'Note Content', 'gravityview' ); ?></label>
		<textarea name="note-content" id="gv-entry-note-content"></textarea>

		<button type="submit" class="button gv-add-note-submit"><?php esc_attr_e( 'Add Note', 'gravityview' ); ?></button>
	</div>
</form>