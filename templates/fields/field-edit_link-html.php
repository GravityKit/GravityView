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

if ( ! $gravityview->field->form_id || ! ( $form = GFAPI::get_form( $gravityview->field->form_id ) ) ) {
	$form = $gravityview->view->form->form;
}

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

$link_text = empty( $field_settings['edit_link'] ) ? __( 'Edit Entry', 'gravityview' ) : $field_settings['edit_link'];

$link_atts = array();
if ( ! empty( $field_settings['new_window'] ) ) {
	$link_atts['target'] = '_blank';
}

$output = apply_filters( 'gravityview_entry_link', GravityView_API::replace_variables( $link_text, $form, $entry ), $gravityview );

$href = GravityView_Edit_Entry::get_edit_link( $entry, $gravityview->view->ID, $post ? $post->ID : null );

echo gravityview_get_link( $href, $output, $link_atts );
