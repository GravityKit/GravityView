<?php
/**
 * The default delete link field output template.
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

if ( ! class_exists( 'GravityView_Delete_Entry' ) ) {
	return;
}

// Only show the link to logged-in users with the right caps.
if ( ! GravityView_Delete_Entry::check_user_cap_delete_entry( $entry, $field_settings ) ) {
	return;
}

$link_text = empty( $field_settings['delete_link'] ) ? __( 'Delete Entry', 'gravityview' ) : $field_settings['delete_link'];

$link_text = apply_filters( 'gravityview_entry_link', GravityView_API::replace_variables( $link_text, $form, $entry ) );

$href = GravityView_Delete_Entry::get_delete_link( $entry, $view_id );

$attributes = array(
	'onclick' => GravityView_Delete_Entry::get_confirm_dialog()
);

echo gravityview_get_link( $href, $link_text, $attributes );
