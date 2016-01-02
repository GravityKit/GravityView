<?php
/**
 * Display the date_created field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );



if( !empty( $field_settings ) && !empty( $field_settings['date_display'] ) && !empty( $value ) ) {

	// If there is a custom PHP date format passed via the date_display setting,
	// use PHP's date format
	$format = $field_settings['date_display'];
	$output = date_i18n( $format, strtotime( $tz_value ) );

} else {

	/**
	 * @filter `gravityview_date_format` Whether to override the Gravity Forms date format with a PHP date format
	 * @see https://codex.wordpress.org/Formatting_Date_and_Time
	 * @param null|string Date Format (default: $field->dateFormat)
	 */
	$format = apply_filters( 'gravityview_date_format', rgar($field, "dateFormat") );
	$output = GFCommon::date_display( $tz_value, $format );

}

echo $output;
