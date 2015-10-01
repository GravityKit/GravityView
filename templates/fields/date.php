<?php
/**
 * Display the Date field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

/**
 * Unix Epoch probably isn't what you're looking for.
 * @since 1.7
 */
if( $value === '1970-01-01' ) {

	/**
	 * Return false to show value. Use `__return_false` callback.
	 */
	$hide_epoch = apply_filters( 'gravityview/fields/date/hide_epoch', true );

	if( $hide_epoch ) {
		return;
	}
}

if( !empty( $field_settings ) && !empty( $field_settings['date_display'] ) && !empty( $value )) {

	// If there is a custom PHP date format passed via the date_display setting,
	// use PHP's date format
	$format = $field_settings['date_display'];
	$output = date_i18n( $format, strtotime( $value ) );

} else {

	// Otherwise, use Gravity Forms, where you can only choose from
	// yyyy-mm-dd, mm-dd-yyyy, and dd-mm-yyyy
	$format = apply_filters( 'gravityview_date_format', rgar($field, "dateFormat") );
	$output = GFCommon::date_display( $value, $format );

}

echo $output;
