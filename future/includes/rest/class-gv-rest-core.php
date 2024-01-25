<?php
namespace GV\REST;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

class Core {
	public static $routes;

	/**
	 * Initialization.
	 */
	public static function init() {
		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_REST ) ) {
			return;
		}

		/** Load routes. */
		require_once gravityview()->plugin->dir( 'future/includes/rest/class-gv-rest-route.php' );
		require_once gravityview()->plugin->dir( 'future/includes/rest/class-gv-rest-views-route.php' );

		self::$routes['views'] = $views = new Views_Route();
		$views->register_routes();
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
