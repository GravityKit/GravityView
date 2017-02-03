<?php
namespace GV\Mocks;

/**
 * This file contains mock code for deprecated functions.
 */

/**
 * @see \GravityView_View_Data::add_view
 * @internal
 * @since future
 */
function GravityView_View_Data_add_view( $_this, $view_id, $atts ) {
	/** Handle array of IDs. */
	if ( is_array( $view_id ) ) {
		foreach ( $view_id as $id ) {
			call_user_func( __FUNCTION__, $_this, $id, $atts );
		}

		if ( ! gravityview()->request->views->count() )
			return array();

		return array_combine(
			array_map( function( $view ) { return $view->ID; }, gravityview()->request->views->all() ),
			array_map( function( $view ) { return $view->_data; }, gravityview()->request->views->all() )
		);
	}

	/** View has been set already. */
	if ( $view = gravityview()->request->views->get( $view_id ) ) {
		do_action( 'gravityview_log_debug', sprintf( 'GravityView_View_Data[add_view] Returning; View #%s already exists.', $view_id ) );
		return $view->_data;
	}

	$view = \GV\View::by_id( $view_id );
	if ( ! $view ) {
		do_action( 'gravityview_log_debug', sprintf( 'GravityView_View_Data[add_view] Returning; View #%s does not exist.', $view_id ) );
		return false;
	}

	/** Doesn't have a connected form. */
	if ( ! $view->_gravityview_form_id ) {
		do_action( 'gravityview_log_debug', sprintf( 'GravityView_View_Data[add_view] Returning; Post ID #%s does not have a connected form.', $view_id ) );
		return false;
	}

	/** Retrieve View settings and merge with defaults and the supplied parameter. */
	$view_settings = gravityview_get_template_settings( $view->ID );
	$view_defaults = wp_parse_args( $view_settings, call_user_func( array( get_class( $_this ), 'get_default_args' ) ) );

	if ( ! empty( $atts ) && is_array( $atts ) ) {
		$atts = shortcode_atts( $view_defaults, $atts );
	} else {
		$atts = $view_defaults;
	}

	unset( $atts['id'], $view_defaults, $view_settings );

	$view->_data = array(
		'id' => $view->ID,
		'view_id' => $view->ID,
		'form_id' => $view->_gravityview_form_id,
		'template_id' => gravityview_get_template_id( $view->ID ),
		'atts' => $atts,
		'fields' => $_this->get_fields( $view->ID ),
		'widgets' => gravityview_get_directory_widgets( $view->ID ),
		'form' => gravityview_get_form( $view->_gravityview_form_id ),
	);

	gravityview()->request->views->add( $view );

	return $view->_data;
}
