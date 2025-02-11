<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The \GV\Field_Renderer class.
 *
 * Houses some preliminary \GV\Field rendering functionality.
 */
class Field_Renderer extends Renderer {

	/**
	 * Renders a \GV\Field instance.
	 *
	 * @param \GV\Field   $request   The field.
	 * @param \GV\View    $view       The view for this context if applicable.
	 * @param \GV\Source  $source   The source (form) for this context if applicable.
	 * @param \GV\Entry   $entry     The entry for this context if applicable.
	 * @param \GV\Request $request The request for this context if applicable.
	 * @param string      $class        The field template class. Default: \GV\Field_HTML_Template'.
	 *
	 * @api
	 * @since 2.0
	 * @since 2.1 Added Field Template class $class parameter
	 *
	 * @return string The rendered Field
	 */
	public function render( Field $field, View $view = null, Source $source = null, Entry $entry = null, Request $request = null, $class = '\GV\Field_HTML_Template' ) {
		if ( is_null( $request ) ) {
			$request = &gravityview()->request;
		}

		if ( ! $request->is_renderable() ) {
			gravityview()->log->error( 'Renderer unable to render View in {request_class} context', array( 'request_class' => get_class( $request ) ) );
			return null;
		}

		/**
		 * Filter the template class that is about to be used to render the view.
		 *
		 * @since 2.0
		 * @param string $class The chosen class - Default: \GV\Field_HTML_Template.
		 * @param \GV\Field $field The field about to be rendered.
		 * @param \GV\View $view The view in this context, if applicable.
		 * @param \GV\Source $source The source (form) in this context, if applicable.
		 * @param \GV\Entry $entry The entry in this context, if applicable.
		 * @param \GV\Request $request The request in this context, if applicable.
		 */
		$class = apply_filters( 'gravityview/template/field/class', $class, $field, $view, $source, $entry, $request );
		if ( ! $class || ! class_exists( $class ) ) {
			gravityview()->log->error( '{template_class} not found', array( 'template_class' => $class ) );
			return null;
		}

		/** @var \GV\Field_Template $class */
		$renderer = new $class( $field, $view, $source, $entry, $request );
		ob_start();
		$renderer->render();
		$renderer->__destruct();
		return ob_get_clean();
	}
}
