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
	 * @param \GV\Field $request The field.
	 * @param \GV\Context $context The render context.
	 *
	 * @api
	 * @since future
	 *
	 * @return string The rendered Field
	 */
	public function render( Field $field, Context $context ) {
		return '';
	}
}
