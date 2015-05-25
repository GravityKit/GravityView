<?php
/**
 * Display Gravity Forms Quiz value letter grade
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

$field = $gravityview_view->getCurrentField();

// If there's no grade, don't continue
if( empty( $field['value'] ) ) {
	return;
}

// Check if grading is enabled for the form. If not set, default to false.
$grading_type_enabled = !empty( $field['form']['gravityformsquiz']['grading'] ) ? $field['form']['gravityformsquiz']['grading'] : 'none';

if( 'letter' === $grading_type_enabled ) {
	echo $field['value'];
} elseif( GFCommon::current_user_can_any( 'manage_options' ) ) {
	$grade_type = __( 'Letter', 'gravityview' );
	printf( esc_html_x( '%s grading is disabled for this form. %sChange the setting%s', '%s is the current Quiz field type ("Letter" or "Pass/Fail")', 'gravityview' ), $grade_type, '<a href="'. admin_url('admin.php?page=gf_edit_forms&amp;view=settings&amp;subview=gravityformsquiz&amp;id='.$gravityview_view->getFormId() ) . '">', '</a>' );
}
