<?php
/**
 * Display the phone field type
 *
 * @since TODO
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

$value = esc_attr( $value );

if( ! empty( $field_settings['link_phone'] ) ) {
	echo "<a href='tel:{$value}'>$value</a>";
} else {
	echo $value;
}