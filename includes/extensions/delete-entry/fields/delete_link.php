<?php

$gravityview_view = GravityView_View::getInstance();

$view_id = $gravityview_view->getViewId();

extract( $gravityview_view->getCurrentField() );

// Only show the link to logged-in users with the rigth caps.
if( !GravityView_Delete_Entry::check_user_cap_delete_entry( $entry, $field_settings ) ) {
	return;
}

$link_text = empty( $field_settings['delete_link'] ) ? __('Delete Entry', 'gravityview') : $field_settings['delete_link'];

$link_text = apply_filters( 'gravityview_entry_link', GravityView_API::replace_variables( $link_text, $form, $entry ) );

$href = GravityView_Delete_Entry::get_delete_link( $entry, $view_id );

$attributes = array(
	'onclick' => GravityView_Delete_Entry::get_confirm_dialog()
);

echo gravityview_get_link( $href, $link_text, $attributes );
