<?php

global $gravityview_view;

extract( $gravityview_view->field_data );

/**
 * date_created is stored in UTC format. Fetch in the current blog's timezone.
 * @param boolean Use timezone-adjusted datetime? If true, adjusts date based on blog's timezone setting. If false, uses UTC setting.
 * @var string
 */
$tz_value = apply_filters( 'gravityview_date_created_adjust_timezone', true ) ? get_date_from_gmt( $value ) : $value;

if( !empty( $field_settings ) && !empty( $field_settings['date_display'] ) && !empty( $tz_value )) {

	// If there is a custom PHP date format passed via the date_display setting,
	// use PHP's date format
	$format = $field_settings['date_display'];
	$output = date( $format, strtotime( $tz_value ) );

} else {

	// Otherwise, use Gravity Forms, where you can only choose from
	// yyyy-mm-dd, mm-dd-yyyy, and dd-mm-yyyy
	$format = apply_filters( 'gravityview_date_format', rgar($field, "dateFormat") );
	$output = GFCommon::date_display( $tz_value, $format );

}

echo $output;
