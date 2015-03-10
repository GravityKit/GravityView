<?php
/**
 * Display the is_fulfilled field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

if( empty( $value ) ) {
	echo __('Not Fulfilled', 'gravityview');
} else {
	echo __('Fulfilled', 'gravityview');
}
