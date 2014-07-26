<?php

global $gravityview_view;

extract( $gravityview_view->field_data );

// Only show the link to logged-in users.
if( !GravityView_Edit_Entry::check_user_cap_edit_entry( $entry ) ) {
	return;
}

$link_text = empty( $field_settings['entry_link_text'] ) ? __('Edit Entry', 'gravity-view') : $field_settings['entry_link_text'];

$output = apply_filters( 'gravityview_entry_link', GravityView_API::replace_variables( $link_text, $form, $entry ) );

$href = GravityView_Edit_Entry::get_edit_link( $entry, $field );

$output = '<a href="'. $href .'">'. $output . '</a>';

echo $output;
