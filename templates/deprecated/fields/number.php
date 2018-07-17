<?php
/**
 * Display the number field type
 *
 * @since 1.13
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

/**
 * @var double|int|string $value
 * @var double|int|string $display_value
 */
extract( $gravityview_view->getCurrentField() );

if( $value !== '' && !empty( $field_settings['number_format'] ) ) {
	$decimals = ( isset( $field_settings['decimals'] ) && $field_settings['decimals'] !== '' ) ? $field_settings['decimals'] : '';
	echo gravityview_number_format( $value, $decimals );
} else {
	echo $display_value;
}