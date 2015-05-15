<div id="publishing-action">
	<?php

	/**
	 * @since 1.5.1
	 */
	do_action( 'gravityview/edit-entry/publishing-action/before', $this->form, $this->entry, $this->view_id );

	?>
	<input class="btn btn-lg button button-large button-primary" type="submit" tabindex="4" value="<?php esc_attr_e( 'Update', 'gravityview'); ?>" name="save" />

	<a class="btn btn-sm button button-small" tabindex="5" href="<?php echo $back_link ?>"><?php esc_attr_e( 'Cancel', 'gravityview' ); ?></a>
	<?php

	/**
	 * @since 1.5.1
	 */
	do_action( 'gravityview/edit-entry/publishing-action/after', $this->form, $this->entry, $this->view_id );

	?>
</div>