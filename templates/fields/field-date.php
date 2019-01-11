<?php
/**
 * The default date field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */
$field_id = $gravityview->field->ID;
$field = $gravityview->field->field;
$value = $gravityview->value;
$field_settings = $gravityview->field->as_configuration();

/**
 * Unix Epoch probably isn't what you're looking for.
 * @since 1.7
 */
if ( $value === '1970-01-01' ) {

	/**
	 * @filter `gravityview/fields/date/hide_epoch` Whether to hide `1970-01-01` dates; that is normally an erroneous date. Return false to show value. Use `__return_false` callback.
	 * @param bool $hide_epoch True: hide values that are 1970-01-01. False: show the value.
	 *
	 * @since 2.0
	 * @param \GV\Template_Context $gravityview The $gravityview context object.
	 */
	$hide_epoch = apply_filters( 'gravityview/fields/date/hide_epoch', true, $gravityview );

	if ( $hide_epoch ) {
		return;
	}
}

if ( ! empty( $field_settings ) && ! empty( $field_settings['date_display'] ) && ! empty( $value ) ) {

	// If there is a custom PHP date format passed via the date_display setting,
	// use PHP's date format
	$format = $field_settings['date_display'];
	$output = date_i18n( $format, strtotime( $value ) );

} else {

	$output = GravityView_Field_Date::date_display( $value, \GV\Utils::get( $field, "dateFormat" ), $field_id );

}

echo $output;
