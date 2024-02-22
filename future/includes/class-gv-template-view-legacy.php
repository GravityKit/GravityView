<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * A legacy fallback View template.
 *
 * Can be used to render old templates as needed.
 */
class View_Legacy_Template extends View_Template {
	/**
	 * Render an old template.
	 */
	public function render() {
		if ( ! class_exists( 'GravityView_Template' ) ) {
			return;
		}

		$context = array(
			'view'    => $this->view,
			'fields'  => $this->view->fields->by_visible( $this->view ),
			'entries' => $this->entries,
			'request' => $this->request,
		);

		global $post;

		if ( $post ) {
			$context['post'] = $post;
		}

		\GV\Mocks\Legacy_Context::push( $context );

		$sections = array( 'header', 'body', 'footer' );

		$sections = apply_filters( 'gravityview_render_view_sections', $sections, $this->view->settings->get( 'template' ) );

		$template = \GravityView_View::getInstance();

		$slug = apply_filters( 'gravityview_template_slug_' . $this->view->settings->get( 'template' ), 'table', 'directory' );

		foreach ( $sections as $section ) {
			$template->render( $slug, $section, false );
		}

		\GV\Mocks\Legacy_Context::pop();
	}
}
