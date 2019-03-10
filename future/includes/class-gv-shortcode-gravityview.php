<?php
namespace GV\Shortcodes;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The [gravityview] shortcode.
 */
class gravityview extends \GV\Shortcode {
	/**
	 * {@inheritDoc}
	 */
	public $name = 'gravityview';

	/**
	 * A stack of calls to track nested shortcodes.
	 */
	public static $callstack = array();

	/**
	 * Process and output the [gravityview] shortcode.
	 *
	 * @param array $passed_atts The attributes passed.
	 * @param string $content The content inside the shortcode.
	 *
	 * @return string|null The output.
	 */
	public function callback( $passed_atts, $content = null ) {
		$request = gravityview()->request;

		if ( $request->is_admin() ) {
			return '';
		}

		$atts = wp_parse_args( $passed_atts, array(
			'id' => 0,
			'view_id' => 0,
			'detail' => null,
		) );
		
		if ( ! $view_id = $atts['id'] ? : $atts['view_id'] ) {
			if ( $atts['detail'] && $view = $request->is_view() ) {
				$view_id = $view->ID;
			}
		}

		$view = \GV\View::by_id( $view_id );

		if ( ! $view ) {
			gravityview()->log->error( 'View does not exist #{view_id}', array( 'view_id' => $view_id ) );
			return '';
		}

		gravityview()->views->set( $view );

		/**
		 * When this shortcode is embedded inside a View we can only display it as a directory. There's no other way.
		 * Try to detect that we're not embedded to allow edit and single contexts.
		 */
		$is_reembedded = false; // Assume not embedded unless detected otherwise.
		if ( in_array( get_class( $request ), array( 'GV\Frontend_Request', 'GV\Mock_Request' ) ) ) {

			if ( ( $_view = $request->is_view() ) && $_view->ID !== $view->ID ) {
				$is_reembedded = true;

			} elseif ( $request->is_entry( $view->form ? $view->form->ID : 0 ) && self::$callstack ) {
				$is_reembedded = true;
			}
		}

		array_push( self::$callstack, true );

		/**
		 * Remove Widgets on a nested embedded View.
		 */
		if ( $is_reembedded ) {
			$view->widgets = new \GV\Widget_Collection();
		}

		$atts = $this->parse_and_sanitize_atts( $atts );

		$view->settings->update( $atts );

		/**
		 * Check permissions.
		 */
		while ( $error = $view->can_render( array( 'shortcode' ), $request ) ) {
			if ( ! is_wp_error( $error ) )
				break;

			switch ( str_replace( 'gravityview/', '', $error->get_error_code() ) ) {
				case 'post_password_required':
					return self::_return( get_the_password_form( $view->ID ) );
				case 'no_form_attached':
					/**
					 * This View has no data source. There's nothing to show really.
					 * ...apart from a nice message if the user can do anything about it.
					 */
					if ( \GVCommon::has_cap( array( 'edit_gravityviews', 'edit_gravityview' ), $view->ID ) ) {
						return self::_return( __( sprintf( 'This View is not configured properly. Start by <a href="%s">selecting a form</a>.', esc_url( get_edit_post_link( $view->ID, false ) ) ), 'gravityview' ) );
					}
					break;
				case 'no_direct_access':
				case 'embed_only':
				case 'not_public':
				default:
					return self::_return( __( 'You are not allowed to view this content.', 'gravityview' ) );
			}
		}

		$is_admin_and_can_view = $view->settings->get( 'admin_show_all_statuses' ) && \GVCommon::has_cap('gravityview_moderate_entries', $view->ID );

		/**
		 * View details.
		 */
		if ( $atts['detail'] ) {
			$entries = $view->get_entries( $request );
			return self::_return( $this->detail( $view, $entries, $atts ) );

		/**
		 * Editing a single entry.
		 */
		} else if ( ! $is_reembedded && ( $entry = $request->is_edit_entry( $view->form ? $view->form->ID : 0 ) ) ) {

			/**
			 * When editing an entry don't render multiple views.
			 */
			if ( ( $selected = \GV\Utils::_GET( 'gvid' ) ) && $view->ID != $selected ) {
				gravityview()->log->notice( 'Entry ID #{entry_id} not rendered because another View ID was passed using `?gvid`: #{selected}', array( 'entry_id' => $entry->ID, 'selected' => $selected ) );
				return self::_return( '' );
			}

			if ( $entry['status'] != 'active' ) {
				gravityview()->log->notice( 'Entry ID #{entry_id} is not active', array( 'entry_id' => $entry->ID ) );
				return self::_return( __( 'You are not allowed to view this content.', 'gravityview' ) );
			}

			if ( apply_filters( 'gravityview_custom_entry_slug', false ) && $entry->slug != get_query_var( \GV\Entry::get_endpoint_name() ) ) {
				gravityview()->log->error( 'Entry ID #{entry_id} was accessed by a bad slug', array( 'entry_id' => $entry->ID ) );
				return self::_return( __( 'You are not allowed to view this content.', 'gravityview' ) );
			}

			if ( $view->settings->get( 'show_only_approved' ) && ! $is_admin_and_can_view ) {
				if ( ! \GravityView_Entry_Approval_Status::is_approved( gform_get_meta( $entry->ID, \GravityView_Entry_Approval::meta_key ) )  ) {
					gravityview()->log->error( 'Entry ID #{entry_id} is not approved for viewing', array( 'entry_id' => $entry->ID ) );
					return self::_return( __( 'You are not allowed to view this content.', 'gravityview' ) );
				}
			}

			$renderer = new \GV\Edit_Entry_Renderer();
			return self::_return( $renderer->render( $entry, $view, $request ) );

		/**
		 * Viewing a single entry.
		 */
		} else if ( ! $is_reembedded && ( $entry = $request->is_entry( $view->form ? $view->form->ID : 0 ) ) ) {
			/**
			 * When viewing an entry don't render multiple views.
			 */
			if ( ( $selected = \GV\Utils::_GET( 'gvid' ) ) && $view->ID != $selected ) {
				return self::_return( '' );
			}

			$entryset = $entry->is_multi() ? $entry->entries : array( $entry );

			foreach ( $entryset as $e ) {
				if ( $e['status'] != 'active' ) {
					gravityview()->log->notice( 'Entry ID #{entry_id} is not active', array( 'entry_id' => $e->ID ) );
					return self::_return( __( 'You are not allowed to view this content.', 'gravityview' ) );
				}

				if ( apply_filters( 'gravityview_custom_entry_slug', false ) && $e->slug != get_query_var( \GV\Entry::get_endpoint_name() ) ) {
					gravityview()->log->error( 'Entry ID #{entry_id} was accessed by a bad slug', array( 'entry_id' => $e->ID ) );
					return self::_return( __( 'You are not allowed to view this content.', 'gravityview' ) );
				}

				if ( $view->settings->get( 'show_only_approved' ) && ! $is_admin_and_can_view ) {
					if ( ! \GravityView_Entry_Approval_Status::is_approved( gform_get_meta( $e->ID, \GravityView_Entry_Approval::meta_key ) )  ) {
						gravityview()->log->error( 'Entry ID #{entry_id} is not approved for viewing', array( 'entry_id' => $e->ID ) );
						return self::_return( __( 'You are not allowed to view this content.', 'gravityview' ) );
					}
				}

				$error = \GVCommon::check_entry_display( $e->as_entry(), $view );

				if ( is_wp_error( $error ) ) {
					gravityview()->log->error( 'Entry ID #{entry_id} is not approved for viewing: {message}', array( 'entry_id' => $e->ID, 'message' => $error->get_error_message() ) );
					return self::_return( __( 'You are not allowed to view this content.', 'gravityview' ) );
				}
			}

			$renderer = new \GV\Entry_Renderer();
			return self::_return( $renderer->render( $entry, $view, $request ) );

		/**
		 * Just this view.
		 */
		} else {
			if ( $is_reembedded ) {
				
				// Mock the request with the actual View, not the global one
				$mock_request = new \GV\Mock_Request();
				$mock_request->returns['is_view'] = $view;
				$mock_request->returns['is_entry'] = $request->is_entry( $view->form ? $view->form->ID : 0 );
				$mock_request->returns['is_edit_entry'] = $request->is_edit_entry( $view->form ? $view->form->ID : 0 );
				$mock_request->returns['is_search'] = $request->is_search();

				$request = $mock_request;
			}

			$renderer = new \GV\View_Renderer();
			return self::_return( $renderer->render( $view, $request ) );
		}
	}

	/**
	 * Validate attributes passed to the [gravityview] shortcode. Supports {get} Merge Tags values.
	 *
	 * Attributes passed to the shortcode are compared to registered attributes {@see \GV\View_Settings::defaults}
	 * Only attributes that are defined will be allowed through.
	 *
	 * Then, {get} merge tags are replaced with their $_GET values, if passed
	 *
	 * Then, attributes are sanitized based on the type of setting (number, checkbox, select, radio, text)
	 *
	 * @see \GV\View_Settings::defaults() Only attributes defined in default() are valid to be passed via the shortcode
	 *
	 * @param array $passed_atts Attribute pairs defined to render the View
	 *
	 * @return array Valid and sanitized attribute pairs
	 */
	private function parse_and_sanitize_atts( $passed_atts ) {

		$defaults = \GV\View_Settings::defaults( true );

		$supported_atts = array_fill_keys( array_keys( $defaults ), '' );

		// Whittle down the attributes to only valid pairs
		$filtered_atts = shortcode_atts( $supported_atts, $passed_atts, 'gravityview' );

		// Only keep the passed attributes after making sure that they're valid pairs
		$filtered_atts = array_intersect_key( (array) $passed_atts, $filtered_atts );

		$atts = array();

		foreach( $filtered_atts as $key => $passed_value ) {

			// Allow using GravityView merge tags in shortcode attributes, like {get} and {created_by}
			$passed_value = \GravityView_Merge_Tags::replace_variables( $passed_value );

			switch( $defaults[ $key ]['type'] ) {

				/**
				 * Make sure number fields are numeric.
				 * Also, convert mixed number strings to numbers
				 * @see http://php.net/manual/en/function.is-numeric.php#107326
				 */
				case 'number':
					if( is_numeric( $passed_value ) ) {
						$atts[ $key ] = ( $passed_value + 0 );
					}
					break;

				// Checkboxes should be 1 or 0
				case 'checkbox':
					$atts[ $key ] = gv_empty( $passed_value, true, false ) ? 0 : 1;
					break;

				/**
				 * Only allow values that are defined in the settings
				 */
				case 'select':
				case 'radio':
					$options = isset( $defaults[ $key ]['choices'] ) ? $defaults[ $key ]['choices'] : $defaults[ $key ]['options'];
					if( in_array( $passed_value, array_keys( $options ) ) ) {
						$atts[ $key ] = $passed_value;
					}
					break;

				case 'text':
				default:
					$atts[ $key ] = $passed_value;
					break;
			}
		}

		$atts['detail'] = \GV\Utils::get( $passed_atts, 'detail', null );

		return $atts;
	}

	/**
	 * Output view details.
	 *
	 * @param \GV\View $view The View.
	 * @param \GV\Entry_Collection $entries The calculated entries.
	 * @param array $atts The shortcode attributes (with defaults).
	 *
	 * @return string The output.
	 */
	private function detail( $view, $entries, $atts ) {
		$output = '';

		switch ( $key = $atts['detail'] ):
			case 'total_entries':
				$output = number_format_i18n( $entries->total() );
				break;
			case 'first_entry':
				$output = number_format_i18n( min( $entries->total(), $view->settings->get( 'offset' ) + 1 ) );
				break;
			case 'last_entry':
				$output = number_format_i18n( $view->settings->get( 'page_size' ) + $view->settings->get( 'offset' ) );
				break;
			case 'page_size':
				$output = number_format_i18n( $view->settings->get( $key ) );
				break;
		endswitch;

		/**
		 * @filter `gravityview/shortcode/detail/{$detail}` Filter the detail output returned from `[gravityview detail="$detail"]`
		 * @since 1.13
		 * @param string[in,out] $output Existing output
		 *
		 * @since 2.0.3
		 * @param \GV\View $view The view.
		 * @param \GV\Entry_Collection $entries The entries.
		 * @param array $atts The shortcode atts with defaults.
		 */
		$output = apply_filters( "gravityview/shortcode/detail/$key", $output, $view );

		return $output;
	}

	/**
	 * Pop the callstack and return the value.
	 */
	private static function _return( $value ) {
		array_pop( self::$callstack );
		return $value;
	}
}
