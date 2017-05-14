<?php
/**
 * The default radio field output template.
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

$display_type = isset( $field_settings['choice_display'] ) ? $field_settings['choice_display'] : 'value';

$output = '';

if ( floatval( $field_id ) === floor( floatval( $field_id ) ) ) {

	if ( 'value' === $display_type ) {
		// For the complete field value
		$output = $display_value;
	} else {
		$output = RGFormsModel::get_choice_text( $field, $display_value );
	}
} else {
	// For part of the field value
	$entry_keys = array_keys( $entry );
	foreach( $entry_keys as $input_key ) {
		if( is_numeric( $input_key ) && floatval( $input_key ) === floatval( $field_id ) ) {
			if( in_array( $field['type'], array( 'radio', 'checkbox' ) ) && !empty( $entry[ $input_key ] ) ) {
				$output = apply_filters( 'gravityview_field_tick', '<span class="dashicons dashicons-yes"></span>', $entry, $field);
			} else {
				$output = $entry[ $input_key ];
			}
		}
	}
}

echo $output;
