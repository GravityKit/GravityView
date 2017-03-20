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
		 * @todo If not configured, output a warning here...
		 */

		/**
		 * This View is password protected. Output the form.
		 */
		if ( post_password_required( $view->ID ) ) {
			return get_the_password_form( $view->ID );
		}

		/**
		 * @filter `gravityview_template_slug_{$template_id}` Modify the template slug about to be loaded in directory views.
		 * @since 1.6
		 * @param deprecated
		 * @see The `gravityview_get_template_id` filter
		 * @param string $slug Default: 'table'
		 * @param string $view The current view context: directory.
		 */
		$template_slug = apply_filters( 'gravityview_template_slug_' . gravityview_get_template_id( $view->ID ), 'table', 'directory' );

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

			/**
			 * @todo: Stop using _frontend and use something like $request->get_search_criteria() instead
			 */
			$parameters = \GravityView_frontend::get_view_entries_parameters( $view->settings->as_atts(), $view->form->ID );

			$entries = $view->form->entries
				->filter( \GV\GF_Entry_Filter::from_search_criteria( $parameters['search_criteria'] ) )
				->offset( $view->settings->get( 'offset' ) )
				->limit( $parameters['paging']['page_size'] )
				/** @todo: Get the page from the request instead! */
				->page( ( ( $parameters['paging']['offset'] - $view->settings->get( 'offset' ) ) / $parameters['paging']['page_size'] ) + 1 );

			if ( ! empty( $parameters['sorting'] ) ) {
				$field = new \GV\Field();
				$field->ID = $parameters['sorting']['key'];
				$direction = strtolower( $parameters['sorting']['direction'] ) == 'asc' ? \GV\Entry_Sort::ASC : \GV\Entry_Sort::DESC;
				$entries = $entries->sort( new \GV\Entry_Sort( $field, $direction ) );
			}
		} else {
			$entries = new \GV\Enty_Collection();
		}

		/**
		* @filter `gravityview/template/view/class` Filter the template class that is about to be used to render the view.
		* @since future
		* @param string $class The chosen class - Default: \GV\View_Table_Template.
		* @param \GV\View $view The view about to be rendered.
		*/
		$class = apply_filters( 'gravityview/template/view/class', sprintf( '\GV\View_%s_Template', ucfirst( $template_slug ) ), $view );
		$template = new $class( $view, $entries );

		ob_start();
		$template->render();
		return ob_get_clean();
	}
}
