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

// Check if grading is enabled for the form. If not set, default to false.
$grading_type_enabled = !empty( $field['form']['gravityformsquiz']['grading'] ) ? $field['form']['gravityformsquiz']['grading'] : 'none';

if( 'passfail' === $grading_type_enabled ) {

	// By default, the field value is "1" for Pass and "0" for Fail. We want the text.
	echo GFCommon::replace_variables( '{quiz_passfail}', $gravityview_view->getForm(), $gravityview_view->getCurrentEntry() );

} elseif( GVCommon::has_cap( 'gravityforms_edit_forms' ) ) {
	$grade_type = __( 'Pass/Fail', 'gravityview' );
	printf( esc_html_x( '%s grading is disabled for this form. %sChange the setting%s', '%s is the current Quiz field type ("Letter" or "Pass/Fail")', 'gravityview' ), $grade_type, '<a href="'. admin_url('admin.php?page=gf_edit_forms&amp;view=settings&amp;subview=gravityformsquiz&amp;id='.$gravityview_view->getFormId() ) . '">', '</a>' );
}
