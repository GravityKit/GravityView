<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 07-April-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\WP;

use GravityKit\GravityView\Foundation\Helpers\Arr;
use GravityKit\GravityView\Foundation\Logger\Framework as LoggerFramework;
use Closure;

class RESTController {
	const REST_NAMESPACE = 'gk-foundation';

	const REST_VERSION = 1;

	/**
	 * Class instance.
	 *
	 * @since 1.0.11
	 *
	 * @var RESTController
	 */
	private static $_instance;

	/**
	 * Collection of routes used for registering REST API endpoints.
	 *
	 * @since 1.0.11
	 *
	 * @var array
	 */
	public $routes;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.11
	 */
	private function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Returns class instance.
	 *
	 * @since 1.0.11
	 *
	 * @return RESTController
	 */
	public static function get_instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Adds a REST route to the collection of routes to be registered.
	 *
	 * @since 1.0.11
	 *
	 * @param array{version?: integer, endpoint: string, methods: string, callback: callable, permission_callback: callable, args?: array, override: boolean} $route Route to add.
	 *
	 * @return void
	 */
	public function add_route( $route = [] ) {
		$this->routes[] = $route;
	}

	/**
	 * Registers REST routes.
	 *
	 * @since 1.0.11
	 *
	 * @return void
	 */
	public function register_routes() {
		/**
		 * Modifies REST routes object.
		 *
		 * @filter gk/foundation/rest/routes
		 *
		 * @since  1.0.11
		 *
		 * @param array{array{version?: integer, endpoint: string, methods: string, callback: callable, permission_callback: callable, args?: array, override: boolean}} $routes
		 */
		$routes = apply_filters( 'gk/foundation/rest/routes', $this->routes );

		if ( empty( $routes ) ) {
			return;
		}

		array_map( [ $this, 'register_route' ], $routes );
	}

	/**
	 * Registers a REST route.
	 *
	 * @since 1.0.11
	 *
	 * @param array{version?: integer, endpoint: string, methods: string, callback: callable, permission_callback: callable, args?: array, override: boolean} $route Route to register.
	 *
	 * @return void
	 */
	public function register_route( $route ) {
		$required_route_params = [
			'endpoint',
			'methods',
			'callback',
			'permission_callback',
		];

		$missing_route_params = array_diff( $required_route_params, array_keys( $route ) );

		if ( ! empty( $missing_route_params ) ) {
			LoggerFramework::get_instance()->warning(
				sprintf(
					'Unable to register route due to missing parameter(s): %s.',
					join( ', ', $missing_route_params )
				)
			);

			return;
		}

		$result = register_rest_route(
			sprintf(
				'%s/v%d',
				Arr::get( $route, 'namespace', self::REST_NAMESPACE ),
				(int) Arr::get( $route, 'version', self::REST_VERSION )
			),
			$route['endpoint'],
			[
				'methods'             => $route['methods'],
				'callback'            => call_user_func( [ $this, 'process_route' ], $route ),
				'permission_callback' => $route['permission_callback'],
				'args'                => Arr::get( $route, 'args', [] ),
			],
			Arr::get( $route, 'override' )
		);

		if ( ! $result ) {
			LoggerFramework::get_instance()->warning( sprintf( 'Unable to register route %s.', $route['endpoint'] ) );
		}
	}

	/**
	 * Processes route by manually calling the route callback and returning the result.
	 * This allows us to modify the response, if needed, as well as trigger actions before and after the callback and before the route is processed by WP.
	 *
	 * @since 1.0.11
	 *
	 * @param array{version?: integer, endpoint: string, methods: string, callback: callable, permission_callback: callable, args?: array, override: boolean} $route Route to process.
	 *
	 * @return Closure Callback function that will be called by WP_REST_Server.
	 */
	public function process_route( $route ) {
		return function () use ( $route ) {
			/**
			 * Fires before the REST API route is processed.
			 *
			 * @action gk/foundation/rest/route/before
			 *
			 * @since  1.0.11
			 *
			 * @param array $route
			 */
			do_action( 'gk/foundation/rest/route/before', $route );

			$response = call_user_func( $route['callback'] );

			/**
			 * Modifies the REST API route response.
			 *
			 * @action gk/foundation/rest/route/response
			 *
			 * @since  1.0.11
			 *
			 * @param mixed $response
			 * @param array $route
			 */
			$response = apply_filters( 'gk/foundation/rest/route/response', $response, $route );

			/**
			 * Fires after the REST API route is processed.
			 *
			 * @action gk/foundation/rest/route/after
			 *
			 * @since  1.0.11
			 *
			 * @param array $route
			 */
			do_action( 'gk/foundation/rest/route/after', $route );

			return $response;
		};
	}
}
