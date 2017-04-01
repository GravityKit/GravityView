<?php
namespace GV\Mocks;

/**
 * This file contains mock code for deprecated functions.
 */

/**
 * @see \GravityView_View_Data::add_view
 * @internal
 * @since future
 *
 * @return array|false The old array data, or false on error.
 */
function GravityView_View_Data_add_view( $view_id, $atts ) {
	/** Handle array of IDs. */
	if ( is_array( $view_id ) ) {
		foreach ( $view_id as $id ) {
			call_user_func( __FUNCTION__, $id, $atts );
		}

		if ( ! gravityview()->request->views->count() ) {
			return array();
		}

		return array_combine(
			array_map( function( $view ) { return $view->ID; }, gravityview()->request->views->all() ),
			array_map( function( $view ) { return $view->as_data(); }, gravityview()->request->views->all() )
		);
	}

	/** View has been set already. */
	if ( $view = gravityview()->request->views->get( $view_id ) ) {
		do_action( 'gravityview_log_debug', sprintf( 'GravityView_View_Data[add_view] Returning; View #%s already exists.', $view_id ) );
		return $view->as_data();
	}

	$view = \GV\View::by_id( $view_id );
	if ( ! $view ) {
		do_action( 'gravityview_log_debug', sprintf( 'GravityView_View_Data[add_view] Returning; View #%s does not exist.', $view_id ) );
		return false;
	}

	/** Doesn't have a connected form. */
	if ( ! $view->form ) {
		do_action( 'gravityview_log_debug', sprintf( 'GravityView_View_Data[add_view] Returning; Post ID #%s does not have a connected form.', $view_id ) );
		return false;
	}

	/** Update the settings */
	if ( is_array( $atts ) ) {
		$view->settings->update( $atts );
	}

	gravityview()->request->views->add( $view );

	return $view->as_data();
}

/** Add some global fix for field capability discrepancies. */
add_filter( 'gravityview/configuration/fields', function( $fields ) {
	if ( empty( $fields  ) ) {
		return $fields;
	}

	/**
	 * Each view field is saved in a weird capability state by default.
	 *
	 * With loggedin set to false, but a capability of 'read' it introduces
	 *  some logical issues and is not robust. Fix this behavior throughout
	 *  core by making sure capability is '' if log in is not required.
	 *
	 * Perhaps in the UI a fix would be to unite the two fields (as our new
	 *  \GV\Field class already does) into one dropdown:
	 *
	 * Anyone, Logged In Only, ... etc. etc.
	 *
	 * The two "settings" should be as tightly coupled as possible to avoid
	 *  split logic scenarios. Uniting them into one field is the way to go.
	 */

	foreach ( $fields as $position => &$_fields ) {

		if ( empty( $_fields ) ) {
			continue;
		}

		foreach ( $_fields as $uid => &$_field ) {
			if ( ! isset( $_field['only_loggedin'] ) ) {
				continue;
			}
			/** If we do not require login, we don't require a cap. */
			$_field['only_loggedin'] != '1' && ( $_field['only_loggedin_cap'] = '' );
		}
	}
	return $fields;
} );
