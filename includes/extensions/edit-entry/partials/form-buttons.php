<div id="publishing-action">
	<?php

    /**
     * Customise the cancel button link
     *
     * @param $back_link string
     *
     * since 1.11.1
     */
    $back_link = apply_filters( 'gravityview/edit_entry/cancel_link', remove_query_arg( array( 'page', 'view', 'edit' ) ), $this->form, $this->entry, $this->view_id );

	/**
	 * @since 1.5.1
	 */
	do_action( 'gravityview/edit-entry/publishing-action/before', $this->form, $this->entry, $this->view_id );

	?>
	<input id="gform_submit_button_<?php echo esc_attr( $this->form['id'] ); ?>" class="btn btn-lg button button-large gform_button button-primary gv-button-update" type="submit" tabindex="4" value="<?php esc_attr_e( 'Update', 'gravityview'); ?>" name="save" />

	<a class="btn btn-sm button button-small gv-button-cancel" tabindex="5" href="<?php echo esc_url( $back_link ); ?>"><?php esc_attr_e( 'Cancel', 'gravityview' ); ?></a>
	<?php

	/**
	 * @since 1.5.1
	 */
	do_action( 'gravityview/edit-entry/publishing-action/after', $this->form, $this->entry, $this->view_id );

	?>
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="lid" value="<?php echo esc_attr( $this->entry['id'] ); ?>" />
</div>
