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
	 * Process and output the [gravityview] shortcode.
	 *
	 * @param array $atts The attributes passed.
	 * @param string $content The content inside the shortcode.
	 *
	 * @return string|null The output.
	 */
	public function callback( $atts, $content = null ) {

		$request = gravityview()->request;

		if ( $request->is_admin() ) {
			return '';
		}

		$atts = wp_parse_args( $atts, array(
			'id' => 0,
			'view_id' => 0,
			'detail' => null,
			'page_size' => 20,
		) );

		$view = \GV\View::by_id( $atts['id'] ? : $atts['view_id'] );

		if ( ! $view ) {
			gravityview()->log->error( 'View does not exist #{view_id}', array( 'view_id' => $view->ID ) );
			return '';
		}

		/**
		 * When this shortcode is embedded inside a view we can only
		 * display it as a directory. There's no other way.
		 * Try to detect that we're not embedded to allow edit and single contexts.
		 */
		$is_reembedded = true; // Assume as embeded unless detected otherwise.
		if ( in_array( get_class( $request ), array( 'GV\Frontend_Request', 'GV\Mock_Request' ) ) ) {
			if ( ( $_view = $request->is_view() ) && $_view->ID == $view->ID ) {
				$is_reembedded = false;
			}
		}

		/**
		 * Remove Widgets on a nested embedded View.
		 */
		if ( $is_reembedded ) {
			$view->widgets = new \GV\Widget_Collection();
		}

		$view->settings->update( $atts );
		$entries = $view->get_entries( $request );

		if ( post_password_required( $view->ID ) ) {
			gravityview()->log->notice( 'Post password is required for View #{view_id}', array( 'view_id' => $view->ID ) );
			return get_the_password_form( $view->ID );
		}

		if ( ! $view->form  ) {
			gravityview()->log->notice( 'View #{id} has no form attached to it.', array( 'id' => $view->ID ) );

			/**
			 * This View has no data source. There's nothing to show really.
			 * ...apart from a nice message if the user can do anything about it.
			 */
			if ( \GVCommon::has_cap( array( 'edit_gravityviews', 'edit_gravityview' ), $view->ID ) ) {
				return __( sprintf( 'This View is not configured properly. Start by <a href="%s">selecting a form</a>.', esc_url( get_edit_post_link( $view->ID, false ) ) ), 'gravityview' );
			}

			return $content;
		}

		/** Private, pending, draft, etc. */
		$public_states = get_post_stati( array( 'public' => true ) );
		if ( ! in_array( $view->post_status, $public_states ) && ! \GVCommon::has_cap( 'read_gravityview', $view->ID ) ) {
			gravityview()->log->notice( 'The current user cannot access this View #{view_id}', array( 'view_id' => $view->ID ) );
			return __( 'You are not allowed to view this content.', 'gravityview' );
		}

		/**
		 * View details.
		 */
		if ( $atts['detail'] ) {
			return $this->detail( $view, $entries, $atts );

		/**
		 * Editing a single entry.
		 */
		} else if ( ! $is_reembedded && ( $entry = $request->is_edit_entry() ) ) {
			if ( $entry['status'] != 'active' ) {
				gravityview()->log->notice( 'Entry ID #{entry_id} is not active', array( 'entry_id' => $entry->ID ) );
				return __( 'You are not allowed to view this content.', 'gravityview' );
			}

			if ( apply_filters( 'gravityview_custom_entry_slug', false ) && $entry->slug != get_query_var( \GV\Entry::get_endpoint_name() ) ) {
				gravityview()->log->error( 'Entry ID #{entry_id} was accessed by a bad slug', array( 'entry_id' => $entry->ID ) );
				return __( 'You are not allowed to view this content.', 'gravityview' );
			}

			if ( $view->settings->get( 'show_only_approved' ) ) {
				if ( ! \GravityView_Entry_Approval_Status::is_approved( gform_get_meta( $entry->ID, \GravityView_Entry_Approval::meta_key ) )  ) {
					gravityview()->log->error( 'Entry ID #{entry_id} is not approved for viewing', array( 'entry_id' => $entry->ID ) );
					return __( 'You are not allowed to view this content.', 'gravityview' );
				}
			}

			$renderer = new \GV\Edit_Entry_Renderer();
			return $renderer->render( $entry, $view, $request );

		/**
		 * Viewing a single entry.
		 */
		} else if ( ! $is_reembedded && ( $entry = $request->is_entry() ) ) {
			if ( $entry['status'] != 'active' ) {
				gravityview()->log->notice( 'Entry ID #{entry_id} is not active', array( 'entry_id' => $entry->ID ) );
				return __( 'You are not allowed to view this content.', 'gravityview' );
			}

			if ( apply_filters( 'gravityview_custom_entry_slug', false ) && $entry->slug != get_query_var( \GV\Entry::get_endpoint_name() ) ) {
				gravityview()->log->error( 'Entry ID #{entry_id} was accessed by a bad slug', array( 'entry_id' => $entry->ID ) );
				return __( 'You are not allowed to view this content.', 'gravityview' );
			}

			if ( $view->settings->get( 'show_only_approved' ) ) {
				if ( ! \GravityView_Entry_Approval_Status::is_approved( gform_get_meta( $entry->ID, \GravityView_Entry_Approval::meta_key ) )  ) {
					gravityview()->log->error( 'Entry ID #{entry_id} is not approved for viewing', array( 'entry_id' => $entry->ID ) );
					return __( 'You are not allowed to view this content.', 'gravityview' );
				}
			}

			$renderer = new \GV\Entry_Renderer();
			return $renderer->render( $entry, $view, $request );

		/**
		 * Just this view.
		 */
		} else {
			if ( $is_reembedded ) {
				
				// Mock the request with the actual View, not the global one
				$mock_request = new \GV\Mock_Request();
				$mock_request->returns['is_view'] = $view;
				$mock_request->returns['is_entry'] = $request->is_entry();
				$mock_request->returns['is_edit_entry'] = $request->is_edit_entry();
				$mock_request->returns['is_search'] = $request->is_search();

				$request = $mock_request;
			}

			$renderer = new \GV\View_Renderer();
			return $renderer->render( $view, $request );
		}
	}

	/**
	 * Output view details.
	 *
	 * @param \GV\View $view The View.
	 * @param \GV\Entry_Collection $entries The calculated entries.
	 * @param array $atts The shortcode attributes (with defaults).
	 * @param array $view_atts A quirky compatibility parameter where we get the unaltered view atts.
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
				$output = number_format_i18n( $view->settings->get( 'page_size' ) );
				break;
			case 'page_size':
				$output = number_format_i18n( $view->settings->get( $key ) );
				break;
		endswitch;

		return $output;
	}
}
