<?php

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

// Only show the link to logged-in users.
if( !GravityView_Edit_Entry::check_user_cap_edit_entry( $entry ) ) {
	return;
}

$link_text = empty( $field_settings['edit_link'] ) ? __('Edit Entry', 'gravityview') : $field_settings['edit_link'];

$output = apply_filters( 'gravityview_entry_link', GravityView_API::replace_variables( $link_text, $form, $entry ) );

$href = GravityView_Edit_Entry::get_edit_link( $entry, $field );

echo gravityview_get_link( $href, $output );