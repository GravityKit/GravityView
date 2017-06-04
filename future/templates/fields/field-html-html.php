<?php
/**
 * The default HTML field output template.
 *
 * @since future
 */
$field = $gravityview->field->field;
$form = $gravityview->view->form->form;
$entry = $gravityview->entry->as_entry();

echo GravityView_API::replace_variables( $field->content, $form, $entry );
