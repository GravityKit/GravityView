<?php
/**
 * The default form section output template.
 *
 * @since future
 */
$field = $gravityview->field->field;
$form = $gravityview->view->form->form;
$entry = $gravityview->entry->as_entry();

if ( ! empty( $field['description'] ) ) {
	echo GravityView_API::replace_variables( $field['description'], $form, $entry );
}
