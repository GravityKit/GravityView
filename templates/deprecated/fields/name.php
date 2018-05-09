<?php
/**
 * Display the name field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

if ( floatval( $field_id ) != intval( $field_id ) ) {
	echo esc_html( gravityview_get_field_value( $entry, $field_id, $display_value ) );
} else {
	echo gravityview_get_field_value( $entry, $field_id, $display_value );
}
