<?php
/**
 * The default form section output template.
 *
 * @since future
 */
$field_id = $gravityview->field->ID;
$field = $gravityview->field->field;
$value = $gravityview->value;
$form = $gravityview->view->form->form;
$display_value = $gravityview->display_value;
$entry = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

if ( ! empty( $field['description'] ) ) {
	echo GravityView_API::replace_variables( $field['description'], $form, $entry );
}
