<?php
/**
 * The default edit link field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

/** @var \GV\GF_Form $gf_form */
$gf_form = isset( $gravityview->field->form_id ) ? \GV\GF_Form::by_id( $gravityview->field->form_id ) : $gravityview->view->form;
$form    = $gf_form->form;

if ( $gravityview->entry->is_multi() ) {
	$entry = $gravityview->entry->from_field( $gravityview->field );
	$entry = $entry->as_entry();
} else {
	$entry = $gravityview->entry->as_entry();
}

$field_settings = $gravityview->field->as_configuration();

global $post;

if ( ! class_exists( 'GravityView_Edit_Entry' ) ) {
	return;
}

// Only show the link to logged-in users.
if ( ! GravityView_Edit_Entry::check_user_cap_edit_entry( $entry, $gravityview->view->ID ) ) {
	return;
}

$link_text = empty( $field_settings['edit_link'] ) ? __( 'Edit Entry', 'gk-gravityview' ) : $field_settings['edit_link'];

$link_atts = array();
if ( ! empty( $field_settings['new_window'] ) ) {
	$link_atts['target'] = '_blank';
}

$output = apply_filters( 'gravityview_entry_link', GravityView_API::replace_variables( $link_text, $form, $entry ), $gravityview );

$href = GravityView_Edit_Entry::get_edit_link( $entry, $gravityview->view->ID, $post ? $post->ID : null );

$output = gravityview_get_link( $href, $output, $link_atts );

/**
 * Modify the link HTML.
 *
 * @since 2.0
 * @param string $link HTML output of the link
 * @param string $href URL of the link
 * @param \GV\Template_Context $context The context
 */
echo apply_filters( 'gravityview/template/field/entry_link', $output, $href, $gravityview );
