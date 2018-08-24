<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The Field CSV Template class .
 *
 * Attached to a \GV\Field and used by a \GV\Field_Renderer.
 */
class Field_CSV_Template extends Field_Template {
	/**
	 * @var string The template slug to be loaded (like "table", "list", "plain")
	 */
	public static $slug = 'csv';
}
