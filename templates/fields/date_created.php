<?php
/**
 * Display the date_created field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

$properties = array();
if( !empty( $field_settings ) && !empty( $field_settings['date_display'] ) && !empty( $value ) ) {
	$properties['format'] = $field_settings['date_display'];
}

echo GVCommon::format_date( $value, $properties );
