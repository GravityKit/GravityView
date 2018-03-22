<?php
namespace GV\REST;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

class Core {
	/**
	 * Initialization.
	 */
	public static function init() {
	}

	/**
	 * Get namespace for GravityView REST API endpoints
	 *
	 * @since 2.0
	 * @return string
	 */
	public static function get_namespace() {
		return 'gravityview/v1';

	}

	/**
	 * Get root URL for GravityView REST API
	 *
	 * @since 2.0
	 * @return string
	 */
	public static function get_url() {
		return rest_url( self::get_namespace() );
	}
}

/** Load routes. */
require gravityview()->plugin->dir( 'future/includes/rest/class-gv-rest-route.php' );
require gravityview()->plugin->dir( 'future/includes/rest/class-gv-rest-entries-route.php' );
require gravityview()->plugin->dir( 'future/includes/rest/class-gv-rest-views-route.php' );
