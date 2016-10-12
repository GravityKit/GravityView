<?php
/**
 * Display a note, without editing options
 *
 * @since 1.17
 */

/**
 * @action `gravityview/field/notes/scripts` Print scripts and styles required for the Notes field
 * @see GravityView_Field_Notes::enqueue_scripts
 * @since 1.17
 */
do_action( 'gravityview/field/notes/scripts' );
?>
<form method="post" class="gv-note-add">
	<div>
		<input type="hidden" name="action" value="gv_note_add" />
		<input type="hidden" name="entry-slug" value="{entry_slug}" />
		<input type="hidden" name="show-delete" value="{show_delete}" />
        <input type="hidden" name="current-url" value="{url}" />
		{nonce_field}
		{email_fields}

		<div class="gv-note-content-container">
			<label for="gv-note-content-{entry_slug}" class="screen-reader-text"><?php echo GravityView_Field_Notes::strings('content-label'); ?></label>
			<textarea name="gv-note-content" id="gv-note-content-{entry_slug}"></textarea>
		</div>

		<button type="submit" class="button gv-add-note-submit"><?php echo GravityView_Field_Notes::strings('add-note'); ?></button>
	</div>
</form>