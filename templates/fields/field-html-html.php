<?php
/**
 * The default HTML field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field = $gravityview->field->field;
$form  = $gravityview->view->form->form;
$entry = $gravityview->entry->as_entry();

echo GravityView_API::replace_variables( $field->content, $form, $entry );
