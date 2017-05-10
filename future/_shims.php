<?php
/**
 * A collection of shims for PHP 5.2 calling syntax support.
 *
 * PHP 5.2 considers namespace usage a syntax error, so we have to
 *  put function shims in place where we're stubbing code out, i.e. everywhere.
 *
 * Sad!
 *
 * Note: the functions themselves are never called or loaded in PHP 5.2.
 */

function gv_shim_GV_Entry_get_endpoint_name() {
	return \GV\Entry::get_endpoint_name();
}

function gv_shim_GV_View_Collection_from_post( $post ) {
	return \GV\View_Collection::from_post( $post );
}

function gv_shim_GV_View_exists( $id ) {
	return \GV\View::exists( $id );
}

function gv_shim_GV_View_Settings_defaults( $detailed = false, $group = null ) {
	return \GV\View_Settings::defaults( $detailed, $group );
}

function gv_shim_view_ID_getter( $view ) {
	return $view->ID;
}

function gv_shim_view_as_data_caller( $view ) {
	return $view->as_data();
}

function gv_shim_GV_View_by_id( $id ) {
	return \GV\View::by_id( $id );
}

function gv_shim_GV_Mocks_GravityView_frontend_get_view_entries( $args, $form_id, $parameters, $count ) {
	 return \GV\Mocks\GravityView_frontend_get_view_entries( $args, $form_id, $parameters, $count );
}

function gv_shim_GV_View_register_post_type() {
	return \GV\View::register_post_type();
}

function gv_shim_GV_Entry_add_rewrite_endpoint() {
	return \GV\Entry::add_rewrite_endpoint();
}

function gv_shim_new_GV_Dummy_Request() {
	return new \GV\Dummy_Request();
}

function gv_shim_GV_Shortcode_parse( $arg ) {
	return \GV\Shortcode::parse( $arg );
}

function gv_shim_GV_Mocks_GravityView_View_Data_add_view( $view_id, $atts ) {
	return \GV\Mocks\GravityView_View_Data_add_view( $view_id, $atts );
}

function gv_shim_new_GV_View_Settings() {
	return new \GV\View_Settings();
}

function gv_shim_GV_Mocks_GravityView_API_field_label( $form, $field, $entry, $force_show_label ) {
	return \GV\Mocks\GravityView_API_field_label( $form, $field, $entry, $force_show_label );
}

function gv_shim_GV_Mocks_GravityView_API_field_value( $entry, $field_settings, $format ) {
	return \GV\Mocks\GravityView_API_field_value( $entry, $field_settings, $format );
}
