<?php
/**
 * @file form-buttons.php
 * @global GravityView_Edit_Entry_Render $object
 */

if ( 'gform_previous_button' === current_filter() ) {
	if ( $object->show_previous_button || $object->show_update_button ) {
		return; // Will be called later once more
	}
}

if ( 'gform_next_button' === current_filter() ) {
	if ( $object->show_update_button ) {
		return; // Will be called later once more
	}
}

?>
<div id="publishing-action">
	<?php

    /**
     * Modify the cancel button link URL when editing an entry in a View.
     *
     * @since 1.11.1
     * @since 2.11 The cancel link now uses history.back() so the $back_link URL matters less.
     * @param string $back_link Existing URL of the Cancel link.
     * @param array $form The Gravity Forms form.
     * @param array $entry The Gravity Forms entry.
     * @param int $view_id The current View ID.
     */
    $back_link = apply_filters( 'gravityview/edit_entry/cancel_link', remove_query_arg( array( 'page', 'view', 'edit' ) ), $object->form, $object->entry, $object->view_id );

	/**
	 * Run before the publishing action buttons (Update, Cancel) are displayed in the Edit Entry screen.
     *
	 * @since 1.5.1
	 * @param array $form The Gravity Forms form.
	 * @param array $entry The Gravity Forms entry.
	 * @param int $view_id The current View ID.
	 */
	do_action( 'gravityview/edit-entry/publishing-action/before', $object->form, $object->entry, $object->view_id );

	$labels = $object->get_action_labels();

	if ( $object->show_previous_button ) {
		$previous_tabindex = GFCommon::get_tabindex();
		$previous_label    = GFCommon::replace_variables( $labels['previous'], $object->form, $object->entry );
		?>
		<input id="gform_previous_button_<?php echo esc_attr( $object->form['id'] ); ?>" class="btn btn-lg button button-large gform_button button-primary gv-button-previous" type="submit" <?php echo $previous_tabindex; ?> value="<?php echo esc_attr( $previous_label ); ?>" name="save" />
		<?php
	}

	if ( $object->show_next_button ) {
		$next_tabindex = GFCommon::get_tabindex();
		$next_label    = GFCommon::replace_variables( $labels['next'], $object->form, $object->entry );
		?>
		<input id="gform_next_button_<?php echo esc_attr( $object->form['id'] ); ?>" class="btn btn-lg button button-large gform_button button-primary gv-button-next" type="submit" <?php echo $next_tabindex; ?> value="<?php echo esc_attr( $next_label ); ?>" name="save" />
		<?php
	}

	if ( $object->show_update_button ) {
		$update_tabindex = GFCommon::get_tabindex();
		$update_label    = GFCommon::replace_variables( $labels['submit'], $object->form, $object->entry );
		?>
		<input id="gform_submit_button_<?php echo esc_attr( $object->form['id'] ); ?>" class="btn btn-lg button button-large gform_button button-primary gv-button-update" type="submit" <?php echo $update_tabindex; ?> value="<?php echo esc_attr( $update_label ); ?>" name="save" />
		<?php
	}

	$cancel_tabindex = GFCommon::get_tabindex();
	$cancel_label    = GFCommon::replace_variables( $labels['cancel'], $object->form, $object->entry );

	// If the entry has been edited, history.back() will keep pointing to the Edit Entry screen. Go back before editing, please!
	// On first visit, will be history.go(-1) because (0 + 1 * -1).
	// After updating twice, history.go(-3) because (2 + 1 * -1).
	$update_count = (int) \GV\Utils::_POST( 'update_count', 0 );

	/**
	 * Modify the JavaScript code that runs when the Cancel button is clicked.
     *
	 * @since 2.13.4
	 * @param string $back_link Existing "back" of the Cancel link.
	 * @param array $form The Gravity Forms form.
	 * @param array $entry The Gravity Forms entry.
	 * @param int $view_id The current View ID.
	 * @param int $update_count The number of pages to go back based on the # of updates to the edited form.
	 */
	$cancel_onclick_js = apply_filters( 'gravityview/edit_entry/cancel_onclick', 'history.go(' . ( $update_count + 1 ) * -1 . '); return false;', $object->form, $object->entry, $object->view_id, $update_count );

	$cancel_onclick = empty( $cancel_onclick_js ) ? '' : 'onclick="' . esc_attr( $cancel_onclick_js ) . '"';
	?>
	<a class="btn btn-sm button button-small gv-button-cancel" <?php echo $cancel_onclick; ?> <?php echo $cancel_tabindex; ?> href="<?php echo esc_url( $back_link ); ?>"><?php echo esc_attr( $cancel_label ); ?></a>
	<?php

	/**
	 * Runs after the Update and Cancel buttons are displayed in the Edit Entry screen.
	 *
	 * @used-by GravityView_Delete_Entry::add_delete_button()
	 *
	 * @since 1.5.1
     * @since 2.0.13 Added $post_id.
	 *
	 * @param array $form The Gravity Forms form.
	 * @param array $entry The Gravity Forms entry.
	 * @param int $view_id The current View ID.
     * @param int $post_id The current Post ID.
	 */
	do_action( 'gravityview/edit-entry/publishing-action/after', $object->form, $object->entry, $object->view_id, $object->post_id );
	?>
	<input type='hidden' name='update_count' value='<?php echo $update_count + 1; ?>'/>
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="lid" value="<?php echo esc_attr( $object->entry['id'] ); ?>" />
</div>
