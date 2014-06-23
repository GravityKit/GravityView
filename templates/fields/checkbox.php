<?php

global $gravityview_view;

extract( $gravityview_view->field_data );

if( in_array( $field['type'], array( 'radio', 'checkbox' ) ) && !empty( $entry[ $field_id ] ) ) {
	$output = apply_filters( 'gravityview_field_tick', '<span class="dashicons dashicons-yes"></span>', $entry, $field);
} else {
	$output = gravityview_get_field_value( $entry, $field_id, $display_value );
}

echo $output;
