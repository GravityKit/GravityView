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
			->limit( $criteria['paging']['page_size'] )
			->page( ( ( $criteria['paging']['offset'] - $args['offset'] ) / $criteria['paging']['page_size'] ) + 1 );
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

/**
 * The old function does a bit too much, not only does it retrieve
 *  the value for a field, but it also renders some output. We are
 *  stubbing the plain value part of it, the rendering will follow once
 *  our field renderers are ready.
 *
 * @see \GravityView_API::field_value
 * @internal
 * @since future
 *
 * @return null|string The value of a field in an entry.
 */
function GravityView_API_field_value( $entry, $field_settings, $format ) {

	if ( empty( $entry['form_id'] ) || empty( $field_settings['id'] ) ) {
		gravityview()->log->error( 'No entry or field_settings[id] supplied', array( 'data' => array( func_get_args() ) ) );
		return null;
	}

	if ( empty( $entry['id'] ) || ! $entry = \GV\GF_Entry::by_id( $entry['id'] ) ) {
		gravityview()->log->error( 'Invalid \GV\GF_Entry supplied', array( 'data' => $entry ) );
		return null;
	}

	/** Setup the field value context. */
	$context = new \GV\Field_Value_Context();
	$context->entry = $entry;

	/**
	 * Determine the source backend.
	 *
	 * Fields with a numeric ID are Gravity Forms ones.
	 */
	$source = is_numeric( $field_settings['id'] ) ? \GV\Source::BACKEND_GRAVITYFORMS : \GV\Source::BACKEND_INTERNAL;;

	/** Initialize the future field. */
	switch ( $source ):
		/** The Gravity Forms backend. */
		case \GV\Source::BACKEND_GRAVITYFORMS:
			if ( ! $form = \GV\GF_Form::by_id( $entry['form_id'] ) ) {
				gravityview()->log->error( 'No form Gravity Form found for entry', array( 'data' => $entry ) );
				return null;
			}

			if ( ! $field = $form::get_field( $form, $field_settings['id'] ) ) {
				return null;
			}

			$field_type = $field->type;
			break;

		/** Our internal backend. */
		case \GV\Source::BACKEND_INTERNAL:
			if ( ! $field = \GV\Internal_Source::get_field( $field_settings['id'] ) ) {
				return null;
			}

			$field_type = $field->ID;
			break;

		/** An unidentified backend. */
		default:
			gravityview()->log->error( 'Could not determine source for entry', array( 'data' => array( func_get_args() ) ) );
			return null;
	endswitch;

	/** Add the field settings. */
	$field->update_configuration( $field_settings );

	/** Get the value. */
	$display_value = $value = $field->get_value( $context );

	/** Alter the display value according to Gravity Forms. */
	if ( $source == \GV\Source::BACKEND_GRAVITYFORMS ) {
		// Prevent any PHP warnings that may be generated
		ob_start();

		$display_value = \GFCommon::get_lead_field_display( $field->field, $value, $entry['currency'], false, $format );

		if ( $errors = ob_get_clean() ) {
			gravityview()->log->error( 'Errors when calling GFCommon::get_lead_field_display()', array( 'data' => $errors ) );
		}

		$display_value = apply_filters( 'gform_entry_field_value', $display_value, $field->field, $entry->as_entry(), $form->form );

		// prevent the use of merge_tags for non-admin fields
		if ( !empty( $field->field->adminOnly ) ) {
			$display_value = \GravityView_API::replace_variables( $display_value, $form->form, $entry->as_entry() );
		}
	}

	$gravityview_view = \GravityView_View::getInstance();

	// Check whether the field exists in /includes/fields/{$field_type}.php
	// This can be overridden by user template files.
	$field_path = $gravityview_view->locate_template("fields/{$field_type}.php");

	// Set the field data to be available in the templates
	$gravityview_view->setCurrentField( array(
		'form' => isset( $form ) ? $form->form : $gravityview_view->getForm(),
		'field_id' => $field->ID,
		'field' => $field->field,
		'field_settings' => $field->as_configuration(),
		'value' => $value,
		'display_value' => $display_value,
		'format' => $format,
		'entry' => $entry->as_entry(),
		'field_type' => $field_type, /** {@since 1.6} */
		'field_path' => $field_path, /** {@since 1.16} */
	) );

	if ( ! empty( $field_path ) ) {
		gravityview()->log->debug( 'Rendering {template}', array( 'template' => $field_path ) );

		ob_start();
		load_template( $field_path, false );
		$output = ob_get_clean();
	} else {
		// Backup; the field template doesn't exist.
		$output = $display_value;
	}

	// Get the field settings again so that the field template can override the settings
	$field_settings = $gravityview_view->getCurrentField( 'field_settings' );

	/**
	 * @filter `gravityview_field_entry_value_{$field_type}_pre_link` Modify the field value output for a field type before Show As Link setting is applied. Example: `gravityview_field_entry_value_number_pre_link`
	 * @since 1.16
	 * @param string $output HTML value output
	 * @param array  $entry The GF entry array
	 * @param array  $field_settings Settings for the particular GV field
	 * @param array  $field Field array, as fetched from GravityView_View::getCurrentField()
	 */
	$output = apply_filters( "gravityview_field_entry_value_{$field_type}_pre_link", $output, $entry->as_entry(), $field_settings, $gravityview_view->getCurrentField() );

	/**
	 * Link to the single entry by wrapping the output in an anchor tag
	 *
	 * Fields can override this by modifying the field data variable inside the field. See /templates/fields/post_image.php for an example.
	 *
	 */
	if ( ! empty( $field_settings['show_as_link'] ) && ! \gv_empty( $output, false, false ) ) {

		$link_atts = empty( $field_settings['new_window'] ) ? array() : array( 'target' => '_blank' );
		$output = \GravityView_API::entry_link_html( $entry->as_entry(), $output, $link_atts, $field_settings );
	}

	/**
	 * @filter `gravityview_field_entry_value_{$field_type}` Modify the field value output for a field type. Example: `gravityview_field_entry_value_number`
	 * @since 1.6
	 * @param string $output HTML value output
	 * @param array  $entry The GF entry array
	 * @param  array $field_settings Settings for the particular GV field
	 * @param array $field Current field being displayed
	 */
	$output = apply_filters( "gravityview_field_entry_value_$field_type", $output, $entry->as_entry(), $field_settings, $gravityview_view->getCurrentField() );

	/**
	 * @filter `gravityview_field_entry_value` Modify the field value output for all field types
	 * @param string $output HTML value output
	 * @param array  $entry The GF entry array
	 * @param  array $field_settings Settings for the particular GV field
	 * @param array $field_data  {@since 1.6}
	 */
	$output = apply_filters( 'gravityview_field_entry_value', $output, $entry->as_entry(), $field_settings, $gravityview_view->getCurrentField() );

	return $output;
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


/** Add a future fix to make sure field configurations include the form ID. */
add_filter( 'gravityview/view/fields/configuration', function( $fields, $view ) {
	if ( ! $view || empty( $fields ) ) {
		return $fields;
	}

	if ( ! $view->form || ! $view->form->ID ) {
		return $fields;
	}

	/**
	 * In order to instantiate the correct \GV\Field implementation
	 *  we need to provide a form_id inside the configuration.
	 *
	 * @todo Make sure this actually happens in the admin side
	 *  when saving the views.
	 */
	foreach ( $fields as $position => &$_fields ) {
		if ( empty( $_fields ) ) {
			continue;
		}

		foreach ( $_fields as $uid => &$_field ) {
			if ( ! empty( $_field['id'] ) && is_numeric( $_field['id'] ) && empty( $_field['form_id'] ) ) {
				$_field['form_id'] = $view->form->ID;
			}
		}
	}

	return $fields;
}, 10, 2 );
