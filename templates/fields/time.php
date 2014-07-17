<?php

global $gravityview_view;

extract( $gravityview_view->field_data );

if( !empty( $field_settings ) && !empty( $field_settings['date_display'] ) && !empty( $value ) ) {

	// If there is a custom PHP date format passed via the date_display setting,
	// use PHP's date format
	$format = $field_settings['date_display'];
	$output = date( $format, strtotime( $value ) );

} else {

	// Otherwise, use Gravity Forms, where you can only choose from
	// yyyy-mm-dd, mm-dd-yyyy, and dd-mm-yyyy
	$format = apply_filters( 'gravityview_date_format', rgar($field, "dateFormat") );
	$output = GFCommon::date_display( $value, $format );

}

echo $output;
