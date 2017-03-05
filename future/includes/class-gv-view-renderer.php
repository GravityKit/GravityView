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
class View_Renderer {

	/**
	 * Renders a \GV\View instance.
	 *
	 * @param \GV\View $view The View instance to render.
	 * @param \GV\Request $request The request context we're currently in. Default: `gravityview()->request`
	 *
	 * @throws \RuntimeException if this renderer is unable to work with the $request type.
	 *
	 * @api
	 * @since future
	 *
	 * @return string The rendered View.
	 */
	public function render( View $view, Request $request = null ) {
		if ( is_null( $request ) ) {
			$request = &gravityview()->request;
		}

		/**
		 * For now we only know how to render views in a Frontend_Request context.
		 */
		if ( get_class( $request ) != 'GV\Frontend_Request' ) {
			throw new \RuntimeException( 'Renderer unable to render View in ' . get_class( $request ) . ' context.' );
		}

		/**
		 * This View is password protected. Output the form.
		 */
		if ( post_password_required( $view->ID ) ) {
			return get_the_password_form( $view->ID );
		}

		/**
		 * @filter `gravityview_template_slug_{$template_id}` Modify the template slug about to be loaded in directory views.
		 * @since 1.6
		 * @param string $slug Default: 'table'
		 * @param string $view The current view context: directory.
		 */
		$template_slug = apply_filters( 'gravityview_template_slug_' . $view->template->ID, 'table', 'directory' );

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
		 * @todo rewrite the API using Entry_Collection
		 */
		if ( $get_entries ) {
			$sort_columns = $view->settings->get( 'sort_columns' );
			$entries = \GravityView_frontend::get_view_entries( $view->settings->as_atts(), $view->form->ID );
		} else {
			$entries = array( 'count' => null, 'entries' => null, 'paging' => null );
		}

		ob_start();

		$view->template->render( $template_slug );

		return ob_get_clean();
	}
}
