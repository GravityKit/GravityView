<?php

global $gravityview_view;

extract( $gravityview_view->field_data );

$show_label = apply_filters( 'gravityview/fields/select/output_label', false, $entry, $field );

if( $show_label && !empty( $field['choices'] ) && is_array( $field['choices'] ) && !empty( $display_value ) ) {
	$output = RGFormsModel::get_choice_text( $field, $display_value );
} else {
	$output = $display_value;
}

echo $output;
