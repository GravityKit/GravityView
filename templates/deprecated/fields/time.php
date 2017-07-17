<?php
/**
 * Display the time field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

$gv_field = $gravityview_view->getCurrentField();

/** @var string $value Raw time value */
$value = rgget( 'value', $gv_field );

/** @var string $field_id ID of the field being displayed */
$field_id = rgget( 'field_id', $gv_field );

$output = '';

if( '' !== $value ) {

	/** @var GF_Field_Time $field Gravity Forms Time field */
	$field = rgget( 'field', $gv_field );

	$format = $gravityview_view->getCurrentFieldSetting( 'date_display' );

	if ( empty( $format ) ) {

		$field->sanitize_settings();

		$format = GravityView_Field_Time::date_format( $field->timeFormat, $field_id );
	}

	// If there is a custom PHP date format passed via the date_display setting, use PHP's date format
	$output = date_i18n( $format, strtotime( $value ) );
}

echo $output;
