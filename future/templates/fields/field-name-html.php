<?php
/**
 * The default name field output template.
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

if ( floatval( $field_id ) != intval( $field_id ) ) {
	echo esc_html( gravityview_get_field_value( $entry, $field_id, $display_value ) );
} else {
	echo gravityview_get_field_value( $entry, $field_id, $display_value );
}
