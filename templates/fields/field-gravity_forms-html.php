<?php
/**
 * The Gravity Forms field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.19
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

/** @var \GV\GF_Form $gf_form */
$gf_form = isset( $gravityview->field->form_id ) ? \GV\GF_Form::by_id( $gravityview->field->form_id ) : $gravityview->view->form;
$form    = $gf_form->form;

if ( $gravityview->entry->is_multi() ) {
	$entry = $gravityview->entry[ $form['id'] ];
	$entry = $entry->as_entry();
} else {
	$entry = $gravityview->entry->as_entry();
}

$field_settings = $gravityview->field->as_configuration();

GravityView_Field_Gravity_Forms::render_frontend( $field_settings, $form, $entry );
