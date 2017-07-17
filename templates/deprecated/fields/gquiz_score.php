<?php
/**
 * Display Gravity Forms Quiz value Pass/Fail
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

$field = $gravityview_view->getCurrentField();

// If there's no grade, don't continue
if( gv_empty( $field['value'], false, false ) ) {
	return;
}

if( class_exists('GFQuiz') && $gravityview_view->getCurrentFieldSetting('quiz_use_max_score') ) {

	$max_score = GFQuiz::get_instance()->get_max_score( $gravityview_view->getForm() );

	printf( '%d/%d', $field['value'], $max_score );

} else {

	echo $field['value'];

}
