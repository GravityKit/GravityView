<?php
/**
 * Display the date_created field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

/**
 * @filter `gravityview_date_created_adjust_timezone` Whether to adjust the timezone for entries. \n
 * date_created is stored in UTC format. Convert search date into UTC (also used on templates/fields/date_created.php)
 * @since 1.16
 * @param[out,in] boolean $adjust_tz  Use timezone-adjusted datetime? If true, adjusts date based on blog's timezone setting. If false, uses UTC setting. Default: true
 * @param[in] string $context Where the filter is being called from. `display` in this case.
 */
$adjust_tz = apply_filters( 'gravityview_date_created_adjust_timezone', true, 'display' );

/**
 * date_created is stored in UTC format. Fetch in the current blog's timezone if $adjust_tz is true
 */
$tz_value = $adjust_tz ? get_date_from_gmt( $value ) : $value;

if( !empty( $field_settings ) && !empty( $field_settings['date_display'] ) && !empty( $tz_value )) {

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
