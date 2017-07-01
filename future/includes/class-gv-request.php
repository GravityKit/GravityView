<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The Request abstract class.
 *
 * Knows more about the request than anyone else.
 */
abstract class Request {
	public function __construct() {
	}

	/**
	 * Check if WordPress is_admin(), and make sure not DOING_AJAX.
	 *
	 * @todo load-(scripts|styles).php return true for \is_admin()!
	 *
	 * @return boolean
	 */
	public static function is_admin() {
		$doing_ajax = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;
		return is_admin() && ! $doing_ajax;
	}

	/**
	 * This is the frontend.
	 *
	 * @return boolean True or false.
	 */
	public static function is_frontend() {
		return ! is_admin();
	}

	/**
	 * Is this the Add Media / From URL preview request?
	 *
	 * Will not work in WordPress 4.8+
	 *
	 * @return boolean
	 */
	public static function is_add_oembed_preview() {
		/** The preview request is a parse-embed AJAX call without a type set. */
		return ( self::is_ajax() && ! empty( $_POST['action'] ) && $_POST['action'] == 'parse-embed' && ! isset( $_POST['type'] ) );
	}

	/**
	 * Is this an AJAX call in progress?
	 *
	 * @return boolean
	 */
	public static function is_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	/**
	 * Is this a REST request?
	 *
	 * @return boolean
	 */
	public static function is_rest() {
		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}
}

/** Load implementations. */
require gravityview()->plugin->dir( 'future/includes/class-gv-request-frontend.php' );
require gravityview()->plugin->dir( 'future/includes/class-gv-request-admin.php' );
require gravityview()->plugin->dir( 'future/includes/class-gv-request-mock.php' );
