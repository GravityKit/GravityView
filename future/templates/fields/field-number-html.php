<?php
/**
 * The default number field output template.
 *
 * @since future
 */
$value = $gravityview->value;
$form = $gravityview->view->form->form;
$display_value = $gravityview->display_value;
$field_settings = $gravityview->field->as_configuration();

if ( $value !== '' && ! empty( $field_settings['number_format'] ) ) {
	$decimals = ( isset( $field_settings['decimals'] ) && $field_settings['decimals'] !== '' ) ? $field_settings['decimals'] : '';
	echo gravityview_number_format( $value, $decimals );
} else {
	echo $display_value;
}
