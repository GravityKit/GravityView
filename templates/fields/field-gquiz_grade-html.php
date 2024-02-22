<?php
/**
 * The default quiz grade field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$value = $gravityview->value;
$form  = $gravityview->view->form->form;

// If there's no grade, don't continue
if ( gv_empty( $value, false, false ) ) {
	return;
}

// Check if grading is enabled for the form. If not set, default to false.
$grading_type_enabled = ! empty( $form['gravityformsquiz']['grading'] ) ? $form['gravityformsquiz']['grading'] : 'none';

if ( 'letter' === $grading_type_enabled ) {
	echo $value;
} elseif ( GVCommon::has_cap( 'gravityforms_edit_forms' ) ) {
	$grade_type = __( 'Letter', 'gk-gravityview' );
	printf( esc_html_x( '%1$s grading is disabled for this form. %2$sChange the setting%3$s', '%s is the current Quiz field type ("Letter" or "Pass/Fail")', 'gk-gravityview' ), $grade_type, '<a href="' . admin_url( 'admin.php?page=gf_edit_forms&amp;view=settings&amp;subview=gravityformsquiz&amp;id=' . $form['id'] ) . '">', '</a>' );
}
