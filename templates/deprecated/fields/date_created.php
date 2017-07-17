<?php
/**
 * Display the date_created field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

echo GVCommon::format_date( $value, 'format=' . rgar( $field_settings, 'date_display' ) );