<?php
/**
 * Display the time field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

$gv_field = $gravityview_view->getCurrentField();

/** @type string $value Raw time value */
$value = \GV\Utils::_GET( 'value', \GV\Utils::get( $gv_field, 'value' ) );

// strtotime() fails at "00:00 am"; it returns false instead of midnight.
if ( false !== strpos( $value, '00:00' ) ) {
	$value = '00:00';
}

/** @type string $field_id ID of the field being displayed */
$field_id = \GV\Utils::_GET( 'field_id', \GV\Utils::get( $gv_field, 'field_id' ) );

$output = '';

if ( '' !== $value ) {

	/** @type GF_Field_Time $field Gravity Forms Time field */
	$field = \GV\Utils::_GET( 'field', \GV\Utils::get( $gv_field, 'field' ) );

	$format = $gravityview_view->getCurrentFieldSetting( 'date_display' );

	if ( empty( $format ) ) {

		$field->sanitize_settings();

		$format = GravityView_Field_Time::date_format( $field->timeFormat, $field_id );
	}

	// If there is a custom PHP date format passed via the date_display setting, use PHP's date format
	$output = date_i18n( $format, strtotime( $value ) );
}

echo $output;
