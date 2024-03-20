<?php
/**
 * The default quiz field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field          = $gravityview->field->field;
$value          = $gravityview->value;
$form           = $gravityview->view->form->form;
$entry          = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

// If there's no grade, don't continue
if ( gv_empty( $value ) ) {
	return;
}

if ( ! class_exists( 'GFQuiz' ) ) {
	do_action( 'gravityview_log_error', __FILE__ . ': GFQuiz class does not exist.' );
	return;
}

// Get the setting for show/hide explanation
$show_answer = \GV\Utils::get( $field_settings, 'quiz_show_explanation' );

// Update the quiz field so GF generates the output properly
$field->gquizShowAnswerExplanation = ! empty( $show_answer );

// Generate the output
echo GFQuiz::get_instance()->display_quiz_on_entry_detail( $value, $field, $entry, $form );
