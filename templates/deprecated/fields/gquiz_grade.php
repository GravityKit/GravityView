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
if ( gv_empty( $field['value'], false, false ) ) {
	return;
}

// Check if grading is enabled for the form. If not set, default to false.
$grading_type_enabled = ! empty( $field['form']['gravityformsquiz']['grading'] ) ? $field['form']['gravityformsquiz']['grading'] : 'none';

if ( 'letter' === $grading_type_enabled ) {
	echo $field['value'];
} elseif ( GVCommon::has_cap( 'gravityforms_edit_forms' ) ) {
	$grade_type = __( 'Letter', 'gk-gravityview' );
	printf( esc_html_x( '%1$s grading is disabled for this form. %2$sChange the setting%3$s', '%s is the current Quiz field type ("Letter" or "Pass/Fail")', 'gk-gravityview' ), $grade_type, '<a href="' . admin_url( 'admin.php?page=gf_edit_forms&amp;view=settings&amp;subview=gravityformsquiz&amp;id=' . $gravityview_view->getFormId() ) . '">', '</a>' );
}
