<?php
/**
 * The default quiz score field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$display_value  = $gravityview->display_value;
$form           = $gravityview->view->form->form;
$field_settings = $gravityview->field->as_configuration();

// If there's no grade, don't continue
if ( gv_empty( $display_value, false, false ) ) {
	return;
}

if ( class_exists( 'GFQuiz' ) && $field_settings['quiz_use_max_score'] ) {

	$max_score = GFQuiz::get_instance()->get_max_score( $form );

	printf( '%d/%d', $display_value, $max_score );

} else {

	echo $display_value;

}
