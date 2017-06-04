<?php
/**
 * The default edit link field output template.
 *
 * @since future
 */
$field_id = $gravityview->field->ID;
$field = $gravityview->field->field;
$value = $gravityview->value;
$form = $gravityview->view->form->form;
$display_value = $gravityview->display_value;
$entry = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();
$view_id = $gravityview->view->ID;

if ( ! class_exists( 'GravityView_Edit_Entry' ) ) {
	return;
}

// Only show the link to logged-in users.
if ( ! GravityView_Edit_Entry::check_user_cap_edit_entry( $entry ) ) {
	return;
}

$link_text = empty( $field_settings['edit_link'] ) ? __( 'Edit Entry', 'gravityview' ) : $field_settings['edit_link'];

$link_atts = array();
if ( ! empty( $field_settings['new_window'] ) ) {
	$link_atts['target'] = '_blank';
}

$output = apply_filters( 'gravityview_entry_link', GravityView_API::replace_variables( $link_text, $form, $entry ) );

$href = GravityView_Edit_Entry::get_edit_link( $entry, $view_id );

echo gravityview_get_link( $href, $output, $link_atts );
