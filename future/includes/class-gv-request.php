<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The Request abstract class.
 *
 * Parses and transforms an end-request for views to View objects.
 */
abstract class Request {
	public function __construct() {
	}

	/**
	 * Check if WordPress is_admin(), and make sure not DOING_AJAX.
	 *
	 * @todo load-(scripts|styles).php return true for \is_admin()!
	 *
	 * @api
	 * @since future
	 *
	 * @return bool Pure is_admin or not?
	 */
	public static function is_admin() {
		$doing_ajax = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;
		return is_admin() && ! $doing_ajax;
	}
}

/** Load implementations. */
require gravityview()->plugin->dir( 'future/includes/class-gv-request-frontend.php' );
