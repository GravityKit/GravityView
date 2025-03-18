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
	 * @param \GV\Entry   $entry The Entry instance to render.
	 * @param \GV\View    $view The View connected to the entry.
	 * @param \GV\Request $request The request context we're currently in. Default: `gravityview()->request`
	 *
	 * @api
	 * @since 2.0
	 *
	 * @return string The rendered Entry.
	 */
	public function render( Entry $entry, View $view, Request $request = null ) {
		if ( is_null( $request ) ) {
			$request = &gravityview()->request;
		}

		if ( ! $request->is_renderable() ) {
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
		 * Before rendering a single entry for a specific View ID.
		 *
		 * @since 1.17
		 *
		 * @since 2.0
		 * @param \GV\Entry $entry The entry about to be rendered
		 * @param \GV\View $view The connected view
		 * @param \GV\Request $request The associated request
		 */
		do_action( 'gravityview_render_entry_' . $view->ID, $entry, $view, $request );

		/** Entry does not belong to this view. */
		if ( $view->joins && $entry instanceof Multi_Entry ) {
			$form_ids = array();
			foreach ( $view->joins as $join ) {
				$form_ids[] = $join->join->ID;
				$form_ids[] = $join->join_on->ID;
			}

			foreach ( $entry->entries ?? [] as $e ) {
				if ( ! in_array( $e['form_id'], $form_ids ) ) {
					gravityview()->log->error(
						'The requested entry does not belong to this View. Entry #{entry_id}, #View {view_id}',
						[
							'entry_id' => $e->ID,
							'view_id'  => $view->ID,
						]
					);

					return null;
				}
			}
		} elseif ( $view->form && $view->form->ID != $entry['form_id'] ) {
			gravityview()->log->error(
				'The requested entry does not belong to this View. Entry #{entry_id}, #View {view_id}',
				array(
					'entry_id' => $entry->ID,
					'view_id'  => $view->ID,
				)
			);
			return null;
		}

		/**
		 * Modify the template slug about to be loaded in directory views.
		 *
		 * @since 1.6
		 * @param deprecated
		 * @see The `gravityview_get_template_id` filter
		 * @param string $slug Default: 'table'
		 * @param string $view The current view context: single
		 */
		$template_slug = apply_filters( 'gravityview_template_slug_' . $view->settings->get( 'template_single_entry' ), 'table', 'single' );

		/**
		 * Load a legacy override template if exists.
		 */
		$override = new \GV\Legacy_Override_Template( $view, $entry, null, $request );
		foreach ( array( 'single' ) as $part ) {
			if ( ( $path = $override->get_template_part( $template_slug, $part ) ) && false === strpos( $path, '/deprecated' ) ) {
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
		 * Filter the template class that is about to be used to render the entry.
		 *
		 * @since 2.0
		 * @param string $class The chosen class - Default: \GV\Entry_Table_Template.
		 * @param \GV\Entry $entry The entry about to be rendered.
		 * @param \GV\View $view The view connected to it.
		 * @param \GV\Request $request The associated request.
		 */
		$class = apply_filters( 'gravityview/template/entry/class', sprintf( '\GV\Entry_%s_Template', ucfirst( $template_slug ) ), $entry, $view, $request );
		if ( ! $class || ! class_exists( $class ) ) {
			gravityview()->log->notice( '{template_class} not found, falling back to legacy', array( 'template_class' => $class ) );
			$class = '\GV\Entry_Legacy_Template';
		}
		$template = new $class( $entry, $view, $request );

		add_action(
			'gravityview/template/after',
			$view_id_output = function ( $context ) {
				printf( '<input type="hidden" class="gravityview-view-id" value="%d">', $context->view->ID );
			}
		);

		/** Mock the legacy state for the widgets and whatnot */
		$entries = new Entry_Collection();
		$entries->add( $entry );
		\GV\Mocks\Legacy_Context::push(
			array(
				'view'    => $view,
				'entries' => $entries,
				'entry'   => $entry,
				'request' => $request,
			)
		);

		ob_start();
		$template->render();

		remove_action( 'gravityview/template/after', $view_id_output );

		return ob_get_clean();
	}
}
