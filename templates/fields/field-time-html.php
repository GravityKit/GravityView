<?php
/**
 * The default time field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field_id = $gravityview->field->ID;
$field    = $gravityview->field->field;
$value    = $gravityview->value;

// strtotime() fails at "00:00 am"; it returns false instead of midnight.
if ( false !== strpos( $value, '00:00' ) ) {
	$value = '00:00';
}

$output = '';

if ( '' !== $value ) {

	$format = $gravityview->field->date_display;

	if ( empty( $format ) ) {

		$field->sanitize_settings();

		$format = GravityView_Field_Time::date_format( $field->timeFormat, $field_id );
	}

	// If there is a custom PHP date format passed via the date_display setting, use PHP's date format
	$output = date_i18n( $format, strtotime( $value ) );
}

echo $output;
