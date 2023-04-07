<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 07-April-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\WP;

use GravityKit\GravityView\Foundation\Helpers\Core as CoreHelpers;
use GravityKit\GravityView\Foundation\Helpers\Arr;
use Exception;

class AjaxRouter {
	const WP_AJAX_ACTION = 'gk_foundation_do_ajax';

	const AJAX_ROUTER = 'core';

	/**
	 * Class instance.
	 *
	 * @since 1.0.11
	 *
	 * @var AjaxRouter
	 */
	private static $_instance;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.11
	 */
	private function __construct() {
		add_action( 'wp_ajax_' . self::WP_AJAX_ACTION, [ $this, 'process_ajax_request' ] );
	}

	/**
	 * Returns class instance.
	 *
	 * @since 1.0.11
	 *
	 * @return AjaxRouter
	 */
	public static function get_instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Returns Ajax request defaults.
	 *
	 * @since 1.0.0
	 *
	 * @param string $router Ajax router that will be handling the request.
	 *
	 * @return array
	 */
	public static function get_ajax_params( $router ) {
		return [
			'_wpNonce'      => wp_create_nonce( self::WP_AJAX_ACTION ),
			'_wpRestNonce'  => wp_create_nonce( 'wp_rest' ),
			'_wpAjaxUrl'    => admin_url( 'admin-ajax.php' ),
			'_wpAjaxAction' => self::WP_AJAX_ACTION,
			'ajaxRouter'    => $router ?: self::AJAX_ROUTER,
		];
	}

	/**
	 * Processes Ajax request and routes it to the appropriate endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception
	 *
	 * @return void|mixed Send JSON response if an Ajax request or return the response as is.
	 */
	public function process_ajax_request() {
		$request = wp_parse_args(
			$_POST, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			[
				'nonce'      => null,
				'payload'    => [],
				'ajaxRouter' => null,
				'ajaxRoute'  => null,
			]
		);

		list ( $nonce, $payload, $router, $route ) = array_values( $request );

		if ( ! is_array( $payload ) ) {
			$payload = json_decode( stripslashes_deep( $payload ), true );
		}

		$is_valid_nonce = wp_verify_nonce( $nonce, self::WP_AJAX_ACTION );

		if ( ! wp_doing_ajax() || ! $is_valid_nonce ) {
			wp_die( false, false, [ 'response' => 403 ] );
		}

		/**
		 * Modifies a list of Ajax routes that map to backend functions/class methods. $router groups routes to avoid a name collision (e.g., 'settings', 'licenses').
		 *
		 * @filter gk/foundation/ajax/{$router}/routes
		 *
		 * @since  1.0.0
		 *
		 * @param array[] $routes Ajax route to function/class method map.
		 */
		$ajax_route_to_class_method_map = apply_filters( "gk/foundation/ajax/{$router}/routes", [] );

		$route_callback = Arr::get( $ajax_route_to_class_method_map, $route );

		if ( ! CoreHelpers::is_callable_function( $route_callback ) && ! CoreHelpers::is_callable_class_method( $route_callback ) ) {
			wp_die( false, false, [ 'response' => 404 ] );
		}

		try {
			/**
			 * Fires before the Ajax call is processed.
			 *
			 * @action gk/foundation/ajax/before
			 *
			 * @since  1.0.11
			 *
			 * @param string $router
			 * @param string $route
			 * @param array  $payload
			 */
			do_action( 'gk/foundation/ajax/before', $router, $route, $payload );

			$result = call_user_func( $route_callback, $payload );
		} catch ( Exception $e ) {
			$result = new Exception( $e->getMessage() );
		}

		/**
		 * Modifies Ajax call result.
		 *
		 * @action gk/foundation/ajax/result
		 *
		 * @since  1.0.11
		 *
		 * @param mixed|Exception $result
		 * @param string          $router
		 * @param string          $route
		 * @param array           $payload
		 */
		$result = apply_filters( 'gk/foundation/ajax/result', $result, $router, $route, $payload );

		/**
		 * Fires after the Ajax call is processed.
		 *
		 * @action gk/foundation/ajax/after
		 *
		 * @since  1.0.11
		 *
		 * @param string          $router
		 * @param string          $route
		 * @param array           $payload
		 * @param mixed|Exception $result
		 */
		do_action( 'gk/foundation/ajax/after', $router, $route, $payload, $result );

		return CoreHelpers::process_return( $result );
	}
}
