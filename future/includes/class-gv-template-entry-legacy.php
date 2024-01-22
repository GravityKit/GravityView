<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The Legacy Entry Template class .
 *
 * A back-compatibility layer for old templates to work.
 */
class Entry_Legacy_Template extends Entry_Template {
	/**
	 * Render an old template.
	 */
	public function render() {
		if ( ! class_exists( 'GravityView_Template' ) ) {
			return;
		}

		$entries = new \GV\Entry_Collection();
		$entries->add( $this->entry );

		$context = array(
			'view'    => $this->view,
			'fields'  => $this->view->fields->by_visible( $this->view ),
			'entries' => $entries,
			'entry'   => $this->entry,
			'request' => $this->request,
		);

		global $post;

		if ( $post ) {
			$context['post'] = $post;
		}

		\GV\Mocks\Legacy_Context::push( $context );

		$sections = array( 'single' );

		$sections = apply_filters( 'gravityview_render_view_sections', $sections, $this->view->settings->get( 'template' ) );

		$template = \GravityView_View::getInstance();

		$slug = apply_filters( 'gravityview_template_slug_' . $this->view->settings->get( 'template' ), 'table', 'directory' );

		foreach ( $sections as $section ) {
			$template->render( $slug, $section, false );
		}

		\GV\Mocks\Legacy_Context::pop();
	}
}
