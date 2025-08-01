<?php
/**
 * The default time field output template.
 *
 * @since 2.0
 * @global \GV\Template_Context $gravityview
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', [ 'file' => __FILE__ ] );

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
	$view_field_format = $gravityview->field->date_display;

	if ( empty( $view_field_format ) ) {
		$field->sanitize_settings();

		$view_field_format = GravityView_Field_Time::date_format( $field->timeFormat, $field_id );
	}

	$form_field_format = ( '12' === $field->timeFormat ) ? 'h:i A' : 'H:i';

	$datetime  = DateTime::createFromFormat( $form_field_format, $value, new DateTimeZone( 'UTC' ) );
	$timestamp = $datetime ? $datetime->getTimestamp() : strtotime( $value );

	$output = date_i18n( $view_field_format, $timestamp );
}

echo $output;
