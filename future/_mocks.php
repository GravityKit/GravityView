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

/**
 * @see \GravityView_frontend::get_view_entries
 * @internal
 * @since future
 *
 * @return array The old associative array data as returned by
 *  \GravityView_frontend::get_view_entries(), the paging parameters
 *  and a total count of all entries.
 */
function GravityView_frontend_get_view_entries( $args, $form_id, $parameters, $count ) {
	$form = \GV\GF_Form::by_id( $form_id );

	/**
	 * Kick off all advanced filters.
	 *
	 * Parameters and criteria are pretty much the same thing here, just
	 *  different naming, where `$parameters` are the initial parameters
	 *  calculated for hte view, and `$criteria` are the filtered ones
	 *  retrieved via `GVCommon::calculate_get_entries_criteria`.
	 */
	$criteria = \GVCommon::calculate_get_entries_criteria( $parameters, $form->ID );

	do_action( 'gravityview_log_debug', '[gravityview_get_entries] Final Parameters', $criteria );

	/** ...and all the (now deprectated) filters that usually follow `gravityview_get_entries` */

	/**
	 * @deprecated
	 * Do not use this filter anymore.
	 */
	$entries = apply_filters( 'gravityview_before_get_entries', null, $criteria, $parameters, $count );
	if ( ! is_null( $entries ) ) {
		/**
		 * We've been given an entries result that we can return,
		 *  just set the paging and we're good to go.
		 */
		$paging = rgar( $parameters, 'paging' );
	} else {
		$entries = $form->entries
			->filter( \GV\GF_Entry_Filter::from_search_criteria( $criteria['search_criteria'] ) )
			->offset( $args['offset'] )
			->limit( $criteria['paging']['page_size'] );

		if ( $criteria['paging']['page_size'] ) {
			$entries = $entries->page( ( ( $criteria['paging']['offset'] - $args['offset'] ) / $criteria['paging']['page_size'] ) + 1 );
		}

		if ( ! empty( $criteria['sorting'] ) ) {
			$field = new \GV\Field();
			$field->ID = $criteria['sorting']['key'];
			$direction = strtolower( $criteria['sorting']['direction'] ) == 'asc' ? \GV\Entry_Sort::ASC : \GV\Entry_Sort::DESC;
			$mode = $criteria['sorting']['is_numeric'] ? \GV\Entry_Sort::NUMERIC : \GV\Entry_Sort::ALPHA;
			$entries = $entries->sort( new \GV\Entry_Sort( $field, $direction, $mode ) );
		}

		/** Set paging, count and unwrap the entries. */
		$paging = array(
			'offset' => ( $entries->current_page - 1 ) * $entries->limit,
			'page_size' => $entries->limit,
		);
		$count = $entries->total();
		$entries = array_map( function( $e ) { return $e->as_entry(); }, $entries->all() );
	}

	/** Just one more filter, for compatibility's sake! */

	/**
	 * @deprecated
	 * Do not use this filter anymore.
	 */
	$entries = apply_filters( 'gravityview_entries', $entries, $criteria, $parameters, $count );

	return array( $entries, $paging, $count );
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
