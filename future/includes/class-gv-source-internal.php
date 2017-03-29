<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The \GV\Internal_Source class.
 *
 * Data that comes from within the View itself (like custom content).
 */
class Internal_Source extends Source {
	
	/**
	 * @var string The identifier of the backend used for this source.
	 *
	 * @api
	 * @since future
	 */
	public static $backend = self::BACKEND_INTERNAL;
}
