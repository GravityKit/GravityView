<?php
/**
 * The default phone field output template.
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

$value = esc_attr( $value );

if( ! empty( $field_settings['link_phone'] ) && ! empty( $value ) ) {
	echo gravityview_get_link( 'tel:' . $value, $value );
} else {
	echo $value;
}
