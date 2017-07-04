<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The \GV\Entry_Renderer class.
 *
 * Houses some preliminary \GV\Entry rendering functionality.
 */
class Entry_Renderer extends Renderer {

	/**
	 * Renders a single \GV\Entry instance.
	 *
	 * @param \GV\Entry $entry The Entry instance to render.
	 * @param \GV\View $view The View connected to the entry.
	 * @param \GV\Request $request The request context we're currently in. Default: `gravityview()->request`
	 *
	 * @api
	 * @since future
	 *
	 * @return string The rendered Entry.
	 */
	public function render( Entry $entry, View $view, Request $request = null ) {
		if ( is_null( $request ) ) {
			$request = &gravityview()->request;
		}

		/**
		 * For now we only know how to render views in a Frontend_Request context.
		 */
		if ( ! in_array( get_class( $request ), array( 'GV\Frontend_Request', 'GV\Mock_Request' ) ) ) {
			gravityview()->log->error( 'Renderer unable to render Entry in {request_class} context', array( 'request_class' => get_class( $request ) ) );
			return null;
		}

		/**
		 * This View is password protected. Output the form.
		 */
		if ( post_password_required( $view->ID ) ) {
			return get_the_password_form( $view->ID );
		}

		/**
		 * @action `gravityview_render_entry_{View ID}` Before rendering a single entry for a specific View ID
		 * @since 1.17
		 *
		 * @since future
		 * @param \GV\Entry $entry The entry about to be rendered
		 * @param \GV\View $view The connected view
		 * @param \GV\Request $request The associated request 
		 */
		do_action( 'gravityview_render_entry_' . $view->ID, $entry, $view, $request );

		/** Entry does not belong to this view. */
		if ( $view->form && $view->form->ID != $entry['form_id'] ) {
			gravityview()->log->error( 'The requested entry does not belong to this view. Entry #{entry_id}, #View {view_id}', array( 'entry_id' => $entry->ID, 'view_id' => $view->ID ) );
			return null;
		}

		if ( ! \GravityView_frontend::is_entry_approved( $entry->as_entry(), $view->settings->as_atts() ) ) {
			/**
			 * @filter `gravityview/render/entry/not_visible` Modify the message shown to users when the entry doesn't exist or they aren't allowed to view it.
			 * @since 1.6
			 * @param string $message Default: "You have attempted to view an entry that is not visible or may not exist."
			 */
			$message = apply_filters( 'gravityview/render/entry/not_visible', __( 'You have attempted to view an entry that is not visible or may not exist.', 'gravityview' ) );

			/**
			 * @since 1.6
			 */
			return esc_attr( $message );
		}

		/**
		 * @filter `gravityview_template_slug_{$template_id}` Modify the template slug about to be loaded in directory views.
		 * @since 1.6
		 * @param deprecated
		 * @see The `gravityview_get_template_id` filter
		 * @param string $slug Default: 'table'
		 * @param string $view The current view context: single
		 */
		$template_slug = apply_filters( 'gravityview_template_slug_' . $view->settings->get( 'template' ), 'table', 'single' );

		/**
		 * @filter `gravityview/template/edit/class` Filter the template class that is about to be used to render the entry.
		 * @since future
		 * @param string $class The chosen class - Default: \GV\Entry_Table_Template.
		 * @param \GV\Entry $entry The entry about to be rendered.
		 * @param \GV\View $view The view connected to it.
		 * @param \GV\Request $request The associated request.
		 */
		$class = apply_filters( 'gravityview/template/edit/class', sprintf( '\GV\Entry_%s_Template', ucfirst( $template_slug ) ), $entry, $view, $request );
		if ( ! $class || ! class_exists( $class ) ) {
			gravityview()->log->notice( '{template_class} not found, falling back to legacy', array( 'template_class' => $class ) );
			$class = '\GV\Entry_Legacy_Template';
		}
		$template = new $class( $entry, $view, $request );

		ob_start();
		$template->render();
		printf( '<input type="hidden" class="gravityview-view-id" value="%d">', $view->ID );
		return ob_get_clean();
	}
}
