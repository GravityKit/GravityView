<?php
namespace GV;

use Throwable;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The \GV\View_Renderer class.
 *
 * Houses some preliminary \GV\View rendering functionality.
 */
class View_Renderer extends Renderer {

	/**
	 * Renders a \GV\View instance.
	 *
	 * @since 2.0
	 *
	 * @api
	 *
	 * @param \GV\Request $request The request context we're currently in. Default: `gravityview()->request`
	 *
	 * @param View        $view The View instance to render.
	 *
	 * @return string The rendered View.
	 */
	public function render( View $view, Request $request = null ) {
		if ( is_null( $request ) ) {
			$request = &gravityview()->request;
		}

		if ( ! $request->is_renderable() ) {
			gravityview()->log->error( 'Renderer unable to render View in {request_class} context', array( 'request_class' => get_class( $request ) ) );
			return null;
		}

		// Track that we're rendering this View.
		\GV\View::push_rendering( $view->ID );

		try {
			/**
			 * Modify the template slug about to be loaded in directory views.
			 *
			 * @since 1.6
			 * @deprecated
			 * @see The `gravityview_get_template_id` filter
			 * @param string $slug Default: 'table'
			 * @param string $view The current view context: directory.
			 */
			$template_slug = apply_filters( 'gravityview_template_slug_' . $view->settings->get( 'template' ), 'table', 'directory' );

			/**
			 * Figure out whether to get the entries or not.
			 *
			 * Some contexts don't need initial entries, like the DataTables directory type.
			 *
			 * Whether to get the entries or not.
			 *
			 * @param boolean $get_entries Get entries or not, default: true.
			 */
			$get_entries = apply_filters( 'gravityview_get_view_entries_' . $template_slug, true );

			$hide_until_searched = $view->settings->get( 'hide_until_searched' );

			/**
			 * Hide View data until search is performed.
			 */
			$get_entries = ( $hide_until_searched && ! $request->is_search() ) ? false : $get_entries;

			/**
			 * Fetch entries for this View.
			 */
			if ( $get_entries ) {
				$entries = $view->get_entries( $request );
			} else {
				$entries = new \GV\Entry_Collection();
			}

			/**
			 * Load a legacy override template if exists.
			 */
			$override = new \GV\Legacy_Override_Template( $view, null, null, $request );
			foreach ( array( 'header', 'body', 'footer' ) as $part ) {

				$path = $override->get_template_part( $template_slug, $part );

				if ( $path && false === strpos( $path, '/deprecated' ) ) {
					/**
					 * We have to bail and call the legacy renderer. Crap!
					 */
					gravityview()->log->notice( 'Legacy templates detected in theme {path}', array( 'path' => $path ) );

					/**
					 * Show a warning at the top, if View is editable by the user.
					 */
					$legacy_warning_cb = $this->legacy_template_warning( $view, $path );

					add_action( 'gravityview_before', $legacy_warning_cb );

					$result = $override->render( $template_slug );

					remove_action( 'gravityview_before', $legacy_warning_cb );

					return $result;
				}
			}

			/**
			 * Filter the template class that is about to be used to render the view.
			 *
			 * @since 2.0
			 * @param string $class The chosen class - Default: \GV\View_Table_Template.
			 * @param View $view The view about to be rendered.
			 * @param \GV\Request $request The associated request.
			 */
			$class = apply_filters( 'gravityview/template/view/class', sprintf( '\GV\View_%s_Template', ucfirst( $template_slug ) ), $view, $request );
			if ( ! $class || ! class_exists( $class ) ) {
				gravityview()->log->notice( '{template_class} not found, falling back to legacy', array( 'template_class' => $class ) );
				$class = '\GV\View_Legacy_Template';
			}

			/** @var \GV\View_Table_Template|\GV\View_List_Template|\GV\View_Legacy_Template $template */
			$template = new $class( $view, $entries, $request );

			/**
			 * @var [] $counter A counter incrementing each time a View is rendered.
			 * @since 2.15
			 * @usedby `gravityview/template/view/render` filter
			 */
			static $counter = array();

			$counter[ $view->ID ] = isset( $counter[ $view->ID ] ) ? $counter[ $view->ID ] + 1 : 1;

			/**
			 * Updates the View anchor ID each time the View is rendered.
			 *
			 * @since 2.15
			 * @uses {@var $counter}
			 * @param Template_Context $context
			 */
			add_action(
				'gravityview/template/view/render',
				$add_anchor_id_filter = function ( $context ) use ( &$counter ) {
					/** @see \GV\View::set_anchor_id() */
					$context->view->set_anchor_id( $counter[ $context->view->ID ] );
				}
			);

			$add_search_action_filter = function ( $action ) use ( $view ) {
				return $action . '#' . esc_attr( $view->get_anchor_id() );
			};

			/**
			 * Allow appending the View ID anchor to the search URL.
			 *
			 * @since  2.15
			 *
			 * @param bool   $set_view_id_anchor
			 */
			if ( apply_filters( 'gravityview/widget/search/append_view_id_anchor', true ) ) {
				/**
				 * Append the View anchor ID to the search form action.
				 *
				 * @since 2.15
				 *
				 * @param string $action The search form action URL.
				 *
				 * @uses  {@var View $view}
				 */
				add_filter( 'gravityview/widget/search/form/action', $add_search_action_filter );
			}

			/**
			 * Remove multiple sorting before calling legacy filters.
			 * This allows us to fake it till we make it.
			 */
			$parameters = $view->settings->as_atts();
			if ( ! empty( $parameters['sort_field'] ) && is_array( $parameters['sort_field'] ) ) {
				$parameters['sort_field'] = reset( $parameters['sort_field'] );

				if ( ! empty( $parameters['sort_direction'] ) && is_array( $parameters['sort_direction'] ) ) {
					$parameters['sort_direction'] = reset( $parameters['sort_direction'] );
				}
			}

			/** @todo Deprecate this! */
			$parameters = \GravityView_frontend::get_view_entries_parameters( $parameters, $view->form->ID );

			global $post;

			/** Mock the legacy state for the widgets and whatnot */
			\GV\Mocks\Legacy_Context::push(
				array_merge(
					array(
						'view'    => $view,
						'entries' => $entries,
						'request' => $request,
					),
					empty( $parameters ) ? array() : array(
						'paging'  => $parameters['paging'],
						'sorting' => $parameters['sorting'],
					),
					empty( $post ) ? array() : array(
						'post' => $post,
					)
				)
			);

			add_action(
				'gravityview/template/after',
				$view_id_output = function ( $context ) {
					printf( '<input type="hidden" class="gravityview-view-id" value="%d">', $context->view->ID );
				}
			);

			ob_start();

			try {
				$template->render();

				$output = ob_get_clean();
			} catch ( Throwable $e ) {
				if ( ob_get_level() > 0 ) {
					ob_end_clean();
				}

				throw $e;
			} finally {
				remove_action( 'gravityview/template/after', $view_id_output );
				remove_filter( 'gravityview/template/view/render', $add_anchor_id_filter );
				remove_filter( 'gravityview/widget/search/form/action', $add_search_action_filter );

				\GV\Mocks\Legacy_Context::pop();
			}

			return $output;
		} finally {
			// Ensure the rendering stack is always popped, even if an error occurs.
			\GV\View::pop_rendering();
		}
	}
}
