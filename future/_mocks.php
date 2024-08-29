<?php
namespace GV\Mocks;

/**
 * This file contains mock code for deprecated functions.
 */

/**
 * @see \GravityView_View_Data::add_view
 * @internal
 * @since 2.0
 *
 * @return array|false The old array data, or false on error.
 */
function GravityView_View_Data_add_view( $view_id, $atts, $_this ) {
	/** Handle array of IDs. */
	if ( is_array( $view_id ) ) {
		foreach ( $view_id as $id ) {
			call_user_func( __FUNCTION__, $id, $atts, $_this );
		}

		if ( ! $_this->views->count() ) {
			return array();
		}

		return array_combine(
			array_map(
				function ( $view ) {
					return $view->ID; },
				$_this->views->all()
			),
			array_map(
				function ( $view ) {
					return $view->as_data(); },
				$_this->views->all()
			)
		);
	}

	/** View has been set already. */
	if ( $view = $_this->views->get( $view_id ) ) {
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

	$_this->views->add( $view );

	return $view->as_data();
}

/**
 * @see \GravityView_frontend::get_view_entries
 * @internal
 * @since 2.0
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
	 *  retrieved via \GVCommon::calculate_get_entries_criteria()
	 */
	$criteria = \GVCommon::calculate_get_entries_criteria( $parameters, $form->ID );

	do_action( 'gravityview_log_debug', '[gravityview_get_entries] Final Parameters', $criteria );

	/** ...and all the (now deprecated) filters that usually follow `gravityview_get_entries` */

	/**
	 * @deprecated
	 * Do not use this filter anymore.
	 */
	$entries = apply_filters_ref_array( 'gravityview_before_get_entries', array( null, $criteria, $parameters, &$count ) );

	if ( ! is_null( $entries ) ) {
		/**
		 * We've been given an entries result that we can return,
		 *  just set the paging and we're good to go.
		 */
		$paging = \GV\Utils::get( $parameters, 'paging' );
	} else {
		$entries = new \GV\Entry_Collection();
		if ( $view = \GV\View::by_id( \GV\Utils::get( $args, 'id' ) ) ) {
			$view->settings->update( $args );
			$entries = $view->get_entries( gravityview()->request );
		}

		$page = \GV\Utils::get( $parameters['paging'], 'current_page' ) ?
			: ( ( ( $parameters['paging']['offset'] - $view->settings->get( 'offset' ) ) / $parameters['paging']['page_size'] ) + 1 );

		/** Set paging, count and unwrap the entries. */
		$paging = array(
			'offset'    => ( $page - 1 ) * $view->settings->get( 'page_size' ),
			'page_size' => $view->settings->get( 'page_size' ),
		);
		/**
		 * GF_Query does not subtract the offset, we have to subtract it ourselves.
		 */
		$count   = $entries->total() - ( gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ? $view->settings->get( 'offset' ) : 0 );
		$entries = array_map(
			function ( $e ) {
				return $e->as_entry();
			},
			$entries->all()
		);
	}

	/** Just one more filter, for compatibility's sake! */

	/**
	 * @deprecated
	 * Do not use this filter anymore.
	 */
	$entries = apply_filters_ref_array( 'gravityview_entries', array( $entries, $criteria, $parameters, &$count ) );

	return array( $entries, $paging, $count );
}

/**
 * The old function does a bit too much, not only does it retrieve
 *  the value for a field, but it also renders some output. We are
 *  stubbing the plain value part of it, the rendering will follow once
 *  our field renderers are ready.
 *
 * @see \GravityView_API::field_value
 * @deprecated Use \GV\Field_Template::render()
 * @internal
 * @since 2.0
 *
 * @return null|string The value of a field in an entry.
 */
function GravityView_API_field_value( $entry, $field_settings, $format ) {
	$original_args = func_get_args();

	if ( empty( $entry['form_id'] ) || empty( $field_settings['id'] ) ) {
		gravityview()->log->error( 'No entry or field_settings[id] supplied', array( 'data' => array( $original_args ) ) );
		return null;
	}

	if ( ! empty( $entry['_multi'] ) && ! empty( $field_settings['form_id'] ) && ! empty( $entry['_multi'][ $field_settings['form_id'] ] ) ) {
		$multientry = \GV\Multi_Entry::from_entries( array_map( '\GV\GF_Entry::from_entry', $entry['_multi'] ) );
		$entry      = $entry['_multi'][ $field_settings['form_id'] ];
	}

	if ( empty( $entry['id'] ) || ! $entry = \GV\GF_Entry::by_id( $entry['id'] ) ) {
		gravityview()->log->error( 'Invalid \GV\GF_Entry supplied', array( 'data' => $entry ) );
		return null;
	}

	/**
	 * Determine the source backend.
	 *
	 * Fields with a numeric ID are Gravity Forms ones.
	 */
	$source = is_numeric( $field_settings['id'] ) ? \GV\Source::BACKEND_GRAVITYFORMS : \GV\Source::BACKEND_INTERNAL;

	/** Initialize the future field. */
	switch ( $source ) :
		/** The Gravity Forms backend. */
		case \GV\Source::BACKEND_GRAVITYFORMS:
			if ( ! $form = \GV\GF_Form::by_id( $entry['form_id'] ) ) {
				gravityview()->log->error( 'No form Gravity Form found for entry', array( 'data' => $entry ) );
				return null;
			}

			if ( ! $field = $form::get_field( $form, $field_settings['id'] ) ) {
				return null;
			}

			break;

		/** Our internal backend. */
		case \GV\Source::BACKEND_INTERNAL:
			if ( ! $field = \GV\Internal_Source::get_field( $field_settings['id'] ) ) {
				return null;
			}

			break;

		/** An unidentified backend. */
		default:
			gravityview()->log->error( 'Could not determine source for entry', array( 'data' => array( $original_args ) ) );
			return null;
	endswitch;

	/** Add the field settings. */
	$field->update_configuration( $field_settings );

	$renderer = new \GV\Field_Renderer();
	$views    = gravityview()->views->get();

	if ( $views instanceof \GV\View_Collection ) {
		$view = $views->first();
	} elseif ( $views instanceof \GV\View ) {
		$view = $views;
	} else {
		return null;
	}

	return $renderer->render( $field, $view, \GV\Source::BACKEND_GRAVITYFORMS == $source ? $form : null, isset( $multientry ) ? $multientry : $entry, gravityview()->request );
}

/**
 * Mock out the \GravityView_API::field_label method
 *
 * Uses the new \GV\Field::get_label methods
 *
 * @see \GravityView_API::field_label
 * @internal
 * @since 2.0
 *
 * @return string The label of a field in an entry.
 */
function GravityView_API_field_label( $form, $field_settings, $entry, $force_show_label = false ) {
	$original_args = func_get_args();

	/** A bail condition. */
	$bail = function ( $label, $field_settings, $entry, $force_show_label, $form ) {
		if ( ! empty( $field_settings['show_label'] ) || $force_show_label ) {

			$label = isset( $field_settings['label'] ) ? $field_settings['label'] : '';

			// Use Gravity Forms label by default, but if a custom label is defined in GV, use it.
			if ( ! empty( $field_settings['custom_label'] ) ) {
				$label = \GravityView_API::replace_variables( $field_settings['custom_label'], $form, $entry );
			}

			/**
			 * Append content to a field label.
			 *
			 * @param string $appended_content Content you can add after a label. Empty by default.
			 * @param array $field GravityView field array
			 */
			$label .= apply_filters( 'gravityview_render_after_label', '', $field_settings );
		}

		/**
		 * Modify field label output.
		 *
		 * @since 1.7
		 * @param string $label Field label HTML
		 * @param array $field GravityView field array
		 * @param array $form Gravity Forms form array
		 * @param array $entry Gravity Forms entry array
		 */
		$label = apply_filters( 'gravityview/template/field_label', $label, $field_settings, $form, $entry );

		return $label;
	};

	$label = '';

	if ( ! empty( $entry['_multi'] ) && ! empty( $field_settings['form_id'] ) && ! empty( $entry['_multi'][ $field_settings['form_id'] ] ) ) {
		$entry = $entry['_multi'][ $field_settings['form_id'] ];
		if ( $_form = \GV\GF_Form::by_id( $field_settings['form_id'] ) ) {
			$form = $_form->form;
		}
	}

	if ( empty( $entry['form_id'] ) || empty( $field_settings['id'] ) ) {
		gravityview()->log->error( 'No entry or field_settings[id] supplied', array( 'data' => array( $original_args ) ) );
		return $bail( $label, $field_settings, $entry, $force_show_label, $form );
	}

	if ( empty( $entry['id'] ) || ! $gv_entry = \GV\GF_Entry::by_id( $entry['id'] ) ) {
		gravityview()->log->error( 'Invalid \GV\GF_Entry supplied', array( 'data' => $entry ) );
		return $bail( $label, $field_settings, $entry, $force_show_label, $form );
	}

	$entry = $gv_entry;

	/**
	 * Determine the source backend.
	 *
	 * Fields with a numeric ID are Gravity Forms ones.
	 */
	$source = is_numeric( $field_settings['id'] ) ? \GV\Source::BACKEND_GRAVITYFORMS : \GV\Source::BACKEND_INTERNAL;

	/** Initialize the future field. */
	switch ( $source ) :
		/** The Gravity Forms backend. */
		case \GV\Source::BACKEND_GRAVITYFORMS:
			if ( ! $gf_form = \GV\GF_Form::by_id( $entry['form_id'] ) ) {
				gravityview()->log->error( 'No form Gravity Form found for entry', array( 'data' => $entry ) );
				return $bail( $label, $field_settings, $entry->as_entry(), $force_show_label, $form );
			}

			if ( ! $field = $gf_form::get_field( $gf_form, $field_settings['id'] ) ) {
				gravityview()->log->error(
					'No field found for specified form and field ID #{field_id}',
					array(
						'field_id' => $field_settings['id'],
						'data'     => $form,
					)
				);
				return $bail( $label, $field_settings, $entry->as_entry(), $force_show_label, $gf_form->form );
			}
			if ( empty( $field_settings['show_label'] ) ) {
				/** The label never wins... */
				$field_settings['label'] = '';
			}

			break;

		/** Our internal backend. */
		case \GV\Source::BACKEND_INTERNAL:
			if ( ! $field = \GV\Internal_Source::get_field( $field_settings['id'] ) ) {
				return $bail( $label, $field_settings, $entry->as_entry(), $force_show_label, $form );
			}
			break;

		/** An unidentified backend. */
		default:
			gravityview()->log->error( 'Could not determine source for entry. Using empty field.', array( 'data' => array( $original_args ) ) );
			$field = new \GV\Field();
			break;
	endswitch;

	if ( $force_show_label ) {
		$field_settings['show_label'] = '1';
	}

	/** Add the field settings. */
	$field->update_configuration( $field_settings );

	if ( ! empty( $field->show_label ) ) {

		$label = $field->get_label( null, isset( $gf_form ) ? $gf_form : null, $entry );

		/**
		 * Append content to a field label.
		 *
		 * @param string $appended_content Content you can add after a label. Empty by default.
		 * @param array $field GravityView field array
		 */
		$label .= apply_filters( 'gravityview_render_after_label', '', $field->as_configuration() );

	}

	/**
	 * Modify field label output.
     *
	 * @since 1.7
	 * @param string $label Field label HTML
	 * @param array $field GravityView field array
	 * @param array $form Gravity Forms form array
	 * @param array $entry Gravity Forms entry array
	 */
	return apply_filters( 'gravityview/template/field_label', $label, $field->as_configuration(), isset( $gf_form ) ? $gf_form->form : $form, $entry->as_entry() );
}


/**
 * A manager of legacy global states and contexts.
 *
 * Handles mocking of:
 * - \GravityView_View_Data
 * - \GravityView_View
 * - \GravityView_frontend
 *
 * Allows us to set a specific state globally using the old
 *  containers, then reset it. Useful for legacy code that keeps
 *  on depending on these variables.
 *
 * Some examples right now include template files, utility functions,
 *  some actions and filters that expect the old contexts to be set.
 */
final class Legacy_Context {
	private static $stack = array();

	/**
	 * Set the state depending on the provided configuration.
	 *
	 * Saves current global state and context.
	 *
	 * Configuration keys:
	 *
	 * - \GV\View   view:       sets \GravityView_View::atts, \GravityView_View::view_id,
	 *                               \GravityView_View::back_link_label
	 *                               \GravityView_frontend::context_view_id,
	 *                               \GravityView_View::form, \GravityView_View::form_id
	 * - \GV\Field  field:      sets \GravityView_View::_current_field, \GravityView_View::field_data,
	 * - \GV\Entry  entry:      sets \GravityView_View::_current_entry, \GravityView_frontend::single_entry,
	 *                               \GravityView_frontend::entry
	 * - \WP_Post   post:       sets \GravityView_View::post_id, \GravityView_frontend::post_id,
	 *                               \GravityView_frontend::is_gravityview_post_type,
	 *                               \GravityView_frontend::post_has_shortcode
	 * - array      paging:     sets \GravityView_View::paging
	 * - array      sorting:    sets \GravityView_View::sorting
	 * - array      template:   sets \GravityView_View::template_part_slug, \GravityView_View::template_part_name
	 *
	 * - boolean    in_the_loop sets $wp_actions['loop_start'] and $wp_query::in_the_loop
	 *
	 * also:
	 *
	 * - \GV\Request    request:    sets \GravityView_frontend::is_search, \GravityView_frontend::single_entry,
	 *                                   \GravityView_View::context, \GravityView_frontend::entry
	 *
	 * - \GV\View_Collection    views:      sets \GravityView_View_Data::views
	 * - \GV\Field_Collection   fields:     sets \GravityView_View::fields
	 * - \GV\Entry_Collection   entries:    sets \GravityView_View::entries, \GravityView_View::total_entries
	 *
	 * and automagically:
	 *
	 * - \GravityView_View      data:       sets \GravityView_frontend::gv_output_data
	 *
	 * @param array $configuration The configuration.
	 *
	 * @return void
	 */
	public static function push( $configuration ) {
		array_push( self::$stack, self::freeze() );
		self::load( $configuration );
	}

	/**
	 * Restores last saved state and context.
	 *
	 * @return void
	 */
	public static function pop() {
		self::thaw( array_pop( self::$stack ) );
	}

	/**
	 * Serializes the current configuration as needed.
	 *
	 * @return array The configuration.
	 */
	public static function freeze() {
		global $wp_actions, $wp_query;

		return array(
			'\GravityView_View::atts'                   => \GravityView_View::getInstance()->getAtts(),
			'\GravityView_View::view_id'                => \GravityView_View::getInstance()->getViewId(),
			'\GravityView_View::back_link_label'        => \GravityView_View::getInstance()->getBackLinkLabel( false ),
			'\GravityView_View_Data::views'             => \GravityView_View_Data::getInstance()->views,
			'\GravityView_View::entries'                => \GravityView_View::getInstance()->getEntries(),
			'\GravityView_View::form'                   => \GravityView_View::getInstance()->getForm(),
			'\GravityView_View::form_id'                => \GravityView_View::getInstance()->getFormId(),
			'\GravityView_View::context'                => \GravityView_View::getInstance()->getContext(),
			'\GravityView_View::total_entries'          => \GravityView_View::getInstance()->getTotalEntries(),
			'\GravityView_View::post_id'                => \GravityView_View::getInstance()->getPostId(),
			'\GravityView_View::hide_until_searched'    => \GravityView_View::getInstance()->isHideUntilSearched(),
			'\GravityView_frontend::post_id'            => \GravityView_frontend::getInstance()->getPostId(),
			'\GravityView_frontend::context_view_id'    => \GravityView_frontend::getInstance()->get_context_view_id(),
			'\GravityView_frontend::is_gravityview_post_type' => \GravityView_frontend::getInstance()->isGravityviewPostType(),
			'\GravityView_frontend::post_has_shortcode' => \GravityView_frontend::getInstance()->isPostHasShortcode(),
			'\GravityView_frontend::gv_output_data'     => \GravityView_frontend::getInstance()->getGvOutputData(),
			'\GravityView_View::paging'                 => \GravityView_View::getInstance()->getPaging(),
			'\GravityView_View::sorting'                => \GravityView_View::getInstance()->getSorting(),
			'\GravityView_frontend::is_search'          => \GravityView_frontend::getInstance()->isSearch(),
			'\GravityView_frontend::single_entry'       => \GravityView_frontend::getInstance()->getSingleEntry(),
			'\GravityView_frontend::entry'              => \GravityView_frontend::getInstance()->getEntry(),
			'\GravityView_View::_current_entry'         => \GravityView_View::getInstance()->getCurrentEntry(),
			'\GravityView_View::fields'                 => \GravityView_View::getInstance()->getFields(),
			'\GravityView_View::_current_field'         => \GravityView_View::getInstance()->getCurrentField(),
			'wp_actions[loop_start]'                    => empty( $wp_actions['loop_start'] ) ? 0 : $wp_actions['loop_start'],
			'wp_query::in_the_loop'                     => $wp_query->in_the_loop,
		);
	}

	/**
	 * Deserializes a saved configuration. Modifies the global state.
	 *
	 * @param array $data Saved configuration from self::freeze()
	 */
	public static function thaw( $data ) {
		foreach ( (array) $data as $key => $value ) {
			switch ( $key ) :
				case '\GravityView_View::atts':
					\GravityView_View::getInstance()->setAtts( $value );
					break;
				case '\GravityView_View::view_id':
					\GravityView_View::getInstance()->setViewId( $value );
					break;
				case '\GravityView_View::back_link_label':
					\GravityView_View::getInstance()->setBackLinkLabel( $value );
					break;
				case '\GravityView_View_Data::views':
					\GravityView_View_Data::getInstance()->views = $value;
					break;
				case '\GravityView_View::entries':
					\GravityView_View::getInstance()->setEntries( $value );
					break;
				case '\GravityView_View::form':
					\GravityView_View::getInstance()->setForm( $value );
					break;
				case '\GravityView_View::form_id':
					\GravityView_View::getInstance()->setFormId( $value );
					break;
				case '\GravityView_View::context':
					\GravityView_View::getInstance()->setContext( $value );
					break;
				case '\GravityView_View::total_entries':
					\GravityView_View::getInstance()->setTotalEntries( $value );
					break;
				case '\GravityView_View::post_id':
					\GravityView_View::getInstance()->setPostId( $value );
					break;
				case '\GravityView_View::is_hide_until_searched':
					\GravityView_View::getInstance()->setHideUntilSearched( $value );
					break;
				case '\GravityView_frontend::post_id':
					\GravityView_frontend::getInstance()->setPostId( $value );
					break;
				case '\GravityView_frontend::context_view_id':
					$frontend                  = \GravityView_frontend::getInstance();
					$frontend->context_view_id = $value;
					break;
				case '\GravityView_frontend::is_gravityview_post_type':
					\GravityView_frontend::getInstance()->setIsGravityviewPostType( $value );
					break;
				case '\GravityView_frontend::post_has_shortcode':
					\GravityView_frontend::getInstance()->setPostHasShortcode( $value );
					break;
				case '\GravityView_frontend::gv_output_data':
					\GravityView_frontend::getInstance()->setGvOutputData( $value );
					break;
				case '\GravityView_View::paging':
					\GravityView_View::getInstance()->setPaging( $value );
					break;
				case '\GravityView_View::sorting':
					\GravityView_View::getInstance()->setSorting( $value );
					break;
				case '\GravityView_frontend::is_search':
					\GravityView_frontend::getInstance()->setIsSearch( $value );
					break;
				case '\GravityView_frontend::single_entry':
					\GravityView_frontend::getInstance()->setSingleEntry( $value );
					break;
				case '\GravityView_frontend::entry':
					\GravityView_frontend::getInstance()->setEntry( $value );
					break;
				case '\GravityView_View::_current_entry':
					\GravityView_View::getInstance()->setCurrentEntry( $value );
					break;
				case '\GravityView_View::fields':
					\GravityView_View::getInstance()->setFields( $value );
					break;
				case '\GravityView_View::_current_field':
					\GravityView_View::getInstance()->setCurrentField( $value );
					break;
				case 'wp_actions[loop_start]':
					global $wp_actions;
					$wp_actions['loop_start'] = $value;
					break;
				case 'wp_query::in_the_loop':
					global $wp_query;
					$wp_query->in_the_loop = $value;
					break;
			endswitch;
		}
	}

	/**
	 * Hydrates the legacy context globals as needed.
	 *
	 * @see Legacy_Context::push() for format.
	 *
	 * @return void
	 */
	public static function load( $configuration ) {
		foreach ( (array) $configuration as $key => $value ) {
			switch ( $key ) :
				case 'view':
					$views = new \GV\View_Collection();
					$views->add( $value );

					self::thaw(
						array(
							'\GravityView_View::atts'    => $value->settings->as_atts(),
							'\GravityView_View::view_id' => $value->ID,
							'\GravityView_View::back_link_label' => $value->settings->get( 'back_link_label', null ),
							'\GravityView_View::form'    => $value->form ? $value->form->form : null,
							'\GravityView_View::form_id' => $value->form ? $value->form->ID : null,
							'\GravityView_View::is_hide_until_searched' => $value->settings->get( 'hide_until_searched', null ) && ! gravityview()->request->is_search(),

							'\GravityView_View_Data::views' => $views,
							'\GravityView_frontend::gv_output_data' => \GravityView_View_Data::getInstance(),
							'\GravityView_frontend::context_view_id' => $value->ID,
						)
					);
					break;
				case 'post':
					$has_shortcode = false;
					foreach ( \GV\Shortcode::parse( $value->post_content ) as $shortcode ) {
						if ( 'gravityview' == $shortcode->name ) {
							$has_shortcode = true;
							break;
						}
					}
					self::thaw(
						array(
							'\GravityView_View::post_id' => $value->ID,
							'\GravityView_frontend::post_id' => $value->ID,
							'\GravityView_frontend::is_gravityview_post_type' => 'gravityview' == $value->post_type,
							'\GravityView_frontend::post_has_shortcode' => $has_shortcode,
						)
					);
					break;
				case 'views':
					self::thaw(
						array(
							'\GravityView_View_Data::views' => $value,
							'\GravityView_frontend::gv_output_data' => \GravityView_View_Data::getInstance(),
						)
					);
					break;
				case 'entries':
					self::thaw(
						array(
							'\GravityView_View::entries' => array_map(
								function ( $e ) {
									return $e->as_entry(); },
								$value->all()
							),
							'\GravityView_View::total_entries' => $value->total(),
						)
					);
					break;
				case 'entry':
					self::thaw(
						array(
							'\GravityView_frontend::single_entry' => $value->ID,
							'\GravityView_frontend::entry' => $value->as_entry(),
							'\GravityView_View::_current_entry' => $value->as_entry(),
						)
					);
					break;
				case 'fields':
					self::thaw(
						array(
							'\GravityView_View::fields' => $value->as_configuration(),
						)
					);
					break;
				case 'field':
					self::thaw(
						array(
							'\GravityView_View::_current_field' => array(
								'field_id'       => $value->ID,
								'field'          => $value->field,
								'field_settings' => $value->as_configuration(),
								'form'           => \GravityView_View::getInstance()->getForm(),
								'field_type'     => $value->type,
								/** {@since 1.6} */
																	'entry' => \GravityView_View::getInstance()->getCurrentEntry(),
								'UID'            => $value->UID,

							// 'field_path' => $field_path, /** {@since 1.16} */
							// 'value' => $value,
							// 'display_value' => $display_value,
							// 'format' => $format,
							),
						)
					);
					break;
				case 'request':
					self::thaw(
						array(
							'\GravityView_View::context' => (
								$value->is_entry() ? 'single' :
								( $value->is_edit_entry() ? 'edit' :
										( $value->is_view( false ) ? 'directory' : null )
									)
							),
							'\GravityView_frontend::is_search' => $value->is_search(),
						)
					);

					if ( ! $value->is_entry() ) {
						self::thaw(
							array(
								'\GravityView_frontend::single_entry' => 0,
								'\GravityView_frontend::entry' => 0,
							)
						);
					}
					break;
				case 'paging':
					self::thaw(
						array(
							'\GravityView_View::paging' => $value,
						)
					);
					break;
				case 'sorting':
					self::thaw(
						array(
							'\GravityView_View::sorting' => $value,
						)
					);
					break;
				case 'in_the_loop':
					self::thaw(
						array(
							'wp_query::in_the_loop'  => $value,
							'wp_actions[loop_start]' => $value ? 1 : 0,
						)
					);
					break;
			endswitch;
		}

		global $gravityview_view;
		$gravityview_view = \GravityView_View::getInstance();
	}

	/**
	 * Resets the global state completely.
	 *
	 * Use with utmost care, as filter and action callbacks
	 *  may be added again.
	 *
	 * Does not touch the context stack.
	 *
	 * @return void
	 */
	public static function reset() {
		\GravityView_View::$instance      = null;
		\GravityView_frontend::$instance  = null;
		\GravityView_View_Data::$instance = null;

		global $wp_query, $wp_actions;

		$wp_query->in_the_loop    = false;
		$wp_actions['loop_start'] = 0;
	}
}


/** Add some global fix for field capability discrepancies. */
add_filter(
	'gravityview/configuration/fields',
	function ( $fields ) {
		if ( empty( $fields ) ) {
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

			if ( empty( $_fields ) || ! is_array( $_fields ) ) {
				continue;
			}

			foreach ( $_fields as $uid => &$_field ) {
				if ( ! isset( $_field['only_loggedin'] ) ) {
					continue;
				}
				/** If we do not require login, we don't require a cap. */
				'1' != $_field['only_loggedin'] && ( $_field['only_loggedin_cap'] = '' );
			}
		}
		return $fields;
	}
);


/** Add a future fix to make sure field configurations include the form ID. */
add_filter(
	'gravityview/view/configuration/fields',
	function ( $fields, $view ) {
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

			if ( empty( $_fields ) || ! is_array( $_fields ) ) {
				continue;
			}

			foreach ( $_fields as $uid => &$_field ) {
				if ( ! empty( $_field['id'] ) && is_numeric( $_field['id'] ) && empty( $_field['form_id'] ) ) {
					$_field['form_id'] = $view->form->ID;
				}
			}
		}

		return $fields;
	},
	10,
	2
);


/** Make sure the non-configured notice is not output twice. */
add_action(
	'gravityview/template/after',
	function ( $gravityview = null ) {
		if ( class_exists( '\GravityView_frontend' ) ) {
			global $wp_filter;

			if ( empty( $wp_filter['gravityview_after'] ) ) {
				return;
			}

			foreach ( $wp_filter['gravityview_after']->callbacks[10] as $function_key => $callback ) {
				if ( strpos( $function_key, 'context_not_configured_warning' ) ) {
					unset( $wp_filter['gravityview_after']->callbacks[10][ $function_key ] );
				}
			}
		}
	}
);

add_filter(
	'gravityview/query/is_null_condition',
	function () {
		if ( ! class_exists( $class = '\GV\Mocks\GF_Query_Condition_IS_NULL' ) ) {
			require_once gravityview()->plugin->dir( 'future/_mocks.isnull.php' );
		}

		return $class;
	}
);
