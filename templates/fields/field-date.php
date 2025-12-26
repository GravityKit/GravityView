<?php
/**
 * The default date field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field_id       = $gravityview->field->ID;
$field          = $gravityview->field->field;
$value          = $gravityview->value;
$field_settings = $gravityview->field->as_configuration();

/**
 * Unix Epoch probably isn't what you're looking for.
 *
 * @since 1.7
 */
if ( '1970-01-01' === $value ) {

	/**
	 * Whether to hide values that match the Unix Epoch date (1970-01-01) from the output.
	 *
	 * @since 1.7
	 * @since 2.0 Added $gravityview parameter.
	 *
	 * @param bool                 $hide_epoch  True: hide values that are 1970-01-01. False: show the value. Default: true.
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

	$output = GravityView_Field_Date::date_display( $value, \GV\Utils::get( $field, 'dateFormat' ), $field_id );

}

echo $output;
