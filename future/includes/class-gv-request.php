<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) )
	die();

/**
 * The Request abstract class.
 *
 * Parses and transforms an end-request for views to View objects.
 */
abstract class Request {
	/**
	 * @var \GV\View_Collection The views attached to the current request.
	 *
	 * @api
	 * @since future
	 */
	public $views;
}

/** Load implementations. */
require gravityview()->plugin->dir( 'future/includes/class-gv-request-frontend.php' );
