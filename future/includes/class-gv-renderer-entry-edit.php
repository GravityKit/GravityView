<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The \GV\Edit_Entry_Renderer class.
 *
 * The edit entry renderer.
 */
class Edit_Entry_Renderer extends Entry_Renderer {

	/**
	 * Renders a an editable \GV\Entry instance.
	 *
	 * @param \GV\Entry   $entry The Entry instance to render.
	 * @param \GV\View    $view The View connected to the entry.
	 * @param \GV\Request $request The request context we're currently in. Default: `gravityview()->request`
	 *
	 * @todo Just a wrapper around the old code. Cheating. Needs rewrite :)
	 *
	 * @api
	 * @since 2.0
	 *
	 * @return string The rendered Entry edit screen.
	 */
	public function render( Entry $entry, View $view, Request $request = null ) {
		$entries = new \GV\Entry_Collection();
		$entries->add( $entry );

		\GV\Mocks\Legacy_Context::push(
			array(
				'view'    => $view,
				'entries' => $entries,
			)
		);

		ob_start();

		/**
		 * Triggered when rendering the Edit Entry screen.
		 *
		 * @since 2.0
		 *
		 * @param null        $deprecated Deprecated parameter. Always null.
		 * @param \GV\Entry   $entry      The Entry instance being edited.
		 * @param \GV\View    $view       The View connected to the entry.
		 * @param \GV\Request $request    The request context.
		 */
		do_action( 'gravityview_edit_entry', null, $entry, $view, $request );

		\GV\Mocks\Legacy_Context::pop();

		return ob_get_clean();
	}
}
