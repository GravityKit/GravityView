<div id="publishing-action">
	<?php

    /**
     * @filter `gravityview/edit_entry/cancel_link` Modify the cancel button link URL
     * @since 1.11.1
     * @param string $back_link Existing URL of the Cancel link
     * @param array $form The Gravity Forms form
     * @param array $entry The Gravity Forms entry
     * @param int $view_id The current View ID
     */
    $back_link = apply_filters( 'gravityview/edit_entry/cancel_link', remove_query_arg( array( 'page', 'view', 'edit' ) ), $this->form, $this->entry, $this->view_id );

	/**
	 * @action `gravityview/edit-entry/publishing-action/before` Triggered before the submit buttons in the Edit Entry screen, inside the `<div id="publishing-action">` container.
	 * @since 1.5.1
	 * @param array $form The Gravity Forms form
	 * @param array $entry The Gravity Forms entry
	 * @param int $view_id The current View ID
	 */
	do_action( 'gravityview/edit-entry/publishing-action/before', $this->form, $this->entry, $this->view_id );

	?>
	<input id="gform_submit_button_<?php echo esc_attr( $this->form['id'] ); ?>" class="btn btn-lg button button-large gform_button button-primary gv-button-update" type="submit" tabindex="4" value="<?php esc_attr_e( 'Update', 'gravityview'); ?>" name="save" />

	<a class="btn btn-sm button button-small gv-button-cancel" tabindex="5" href="<?php echo esc_url( $back_link ); ?>"><?php esc_attr_e( 'Cancel', 'gravityview' ); ?></a>
	<?php

	/**
	 * @action `gravityview/edit-entry/publishing-action/after` Triggered after the submit buttons in the Edit Entry screen, inside the `<div id="publishing-action">` container.
	 * @since 1.5.1
	 * @param array $form The Gravity Forms form
	 * @param array $entry The Gravity Forms entry
	 * @param int $view_id The current View ID
	 */
	do_action( 'gravityview/edit-entry/publishing-action/after', $this->form, $this->entry, $this->view_id );

	?>
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="lid" value="<?php echo esc_attr( $this->entry['id'] ); ?>" />
</div>
