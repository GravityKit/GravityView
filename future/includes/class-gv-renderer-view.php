<?php
namespace GV;

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
	 * @param \GV\View $view The View instance to render.
	 * @param \GV\Request $request The request context we're currently in. Default: `gravityview()->request`
	 *
	 * @api
	 * @since 2.0
	 *
	 * @return string The rendered View.
	 */
	public function render( View $view, Request $request = null ) {
		if ( is_null( $request ) ) {
			$request = &gravityview()->request;
		}

		if ( ! in_array( get_class( $request ), array( 'GV\Frontend_Request', 'GV\Mock_Request', 'GV\REST\Request' ) ) ) {
			gravityview()->log->error( 'Renderer unable to render View in {request_class} context', array( 'request_class' => get_class( $request ) ) );
			return null;
		}

		/**
		 * @filter `gravityview_template_slug_{$template_id}` Modify the template slug about to be loaded in directory views.
		 * @since 1.6
		 * @param deprecated
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
		 * @filter `gravityview_get_view_entries_{$template_slug}` Whether to get the entries or not.
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
			if ( ( $path = $override->get_template_part( $template_slug, $part ) ) && strpos( $path, '/deprecated' ) === false ) {
				/**
				 * We have to bail and call the legacy renderer. Crap!
				 */
				gravityview()->log->notice( 'Legacy templates detected in theme {path}', array( 'path' => $path ) );

				/**
				 * Show a warning at the top, if View is editable by the user.
				 */
				add_action( 'gravityview_before', $this->legacy_template_warning( $view, $path ) );

				return $override->render( $template_slug );
			}
		}

		/**
		 * @filter `gravityview/template/view/class` Filter the template class that is about to be used to render the view.
		 * @since 2.0
		 * @param string $class The chosen class - Default: \GV\View_Table_Template.
		 * @param \GV\View $view The view about to be rendered.
		 * @param \GV\Request $request The associated request.
		 */
		$class = apply_filters( 'gravityview/template/view/class', sprintf( '\GV\View_%s_Template', ucfirst( $template_slug ) ), $view, $request );
		if ( ! $class || ! class_exists( $class ) ) {
			gravityview()->log->notice( '{template_class} not found, falling back to legacy', array( 'template_class' => $class ) );
			$class = '\GV\View_Legacy_Template';
		}
		$template = new $class( $view, $entries, $request );

		/** @todo Deprecate this! */
		$parameters = \GravityView_frontend::get_view_entries_parameters( $view->settings->as_atts(), $view->form->ID );

		global $post;

		/** Mock the legacy state for the widgets and whatnot */
		\GV\Mocks\Legacy_Context::push( array_merge( array(
			'view' => $view,
			'entries' => $entries,
			'request' => $request,
		), empty( $parameters ) ? array() : array(
			'paging' => $parameters['paging'],
			'sorting' => $parameters['sorting'],
		), empty( $post ) ? array() : array(
			'post' => $post,
		) ) );

		add_action( 'gravityview/template/after', $view_id_output = function( $context ) {
			printf( '<input type="hidden" class="gravityview-view-id" value="%d">', $context->view->ID );
		} );

		ob_start();
		$template->render();

		remove_action( 'gravityview/template/after', $view_id_output );

		\GV\Mocks\Legacy_Context::pop();
		return ob_get_clean();
	}
}
