<?php
/**
 * @package   GravityView
 * @license   GPL2+
 * @author    Josh Pollock <josh@joshpress.net>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 2.0
 */
namespace GV\REST;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

abstract class Route extends \WP_REST_Controller {
	/**
	 * Route Name
	 *
	 * @since 2.0
	 * @access protected
	 * @var string
	 */
	protected $route_name;

	/**
	 * Sub type, forms {$namespace}/route_name/{id}/sub_type type endpoints
	 *
	 * @since 2.0
	 * @access protected
	 * @var string
	 */
	protected $sub_type;

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {

		// Clear out all errors before we start. This prevents breaking responses when WP_DEBUG_DISPLAY is true.
		$output_buffer = ob_get_clean();

		$namespace = \GV\REST\Core::get_namespace();
		$base      = $this->get_route_name();

		register_rest_route(
			$namespace,
			'/' . $base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'page'    => array(
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'limit'   => array(
							'default'           => 10,
							'sanitize_callback' => 'absint',
						),
						'post_id' => array(
							'default'           => null,
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->create_item_args(),

				),
			)
		);
		register_rest_route(
			$namespace,
			'/' . $base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => array(
							'default' => 'view',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->update_item_args(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default' => false,
						),
					),
				),
			)
		);

		$sub_type = $this->get_sub_type();

		$format = '(?:\.(?P<format>html|json|csv|tsv))?';

		register_rest_route(
			$namespace,
			'/' . $base . '/(?P<id>[\d]+)' . '/' . $sub_type . $format,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_sub_items' ),
					'permission_callback' => array( $this, 'get_sub_items_permissions_check' ),
					'args'                => array(
						'page'    => array(
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'limit'   => array(
							'default'           => 10,
							'sanitize_callback' => 'absint',
						),
						'post_id' => array(
							'default'           => null,
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_sub_item' ),
					'permission_callback' => array( $this, 'create_sub_item_permissions_check' ),
					'args'                => $this->create_sub_item_args(),
				),
			)
		);

		$format = '(?:\.(?P<format>html|json))?';

		register_rest_route(
			$namespace,
			sprintf( '/%s/(?P<id>[\d]+)/%s/(?P<s_id>[\w-]+)%s', $base, $sub_type, $format ),
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_sub_item' ),
					'permission_callback' => array( $this, 'get_sub_item_permissions_check' ),
					'args'                => array(
						'context' => array(
							'default' => 'view',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_sub_item' ),
					'permission_callback' => array( $this, 'update_sub_item_permissions_check' ),
					'args'                => $this->update_sub_item_args(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_sub_item' ),
					'permission_callback' => array( $this, 'delete_sub_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default' => false,
						),
					),
				),
			)
		);

		echo $output_buffer;
	}

	/**
	 * Get route name
	 *
	 * MUST SET route_name property in subclass!
	 *
	 * @since 2.0
	 * @access protected
	 * @return string
	 */
	protected function get_route_name() {
		if ( is_string( $this->route_name ) ) {
			return $this->route_name;
		} else {
			_doing_it_wrong( __METHOD__, __( 'Must set route name in subclass.', 'gk-gravityview' ), '2.0' );
			return '';
		}
	}

	/**
	 * Get sub_type
	 *
	 * MUST SET sub_type property in subclass!
	 *
	 * @since 2.0
	 * @access protected
	 * @return string
	 */
	protected function get_sub_type() {
		if ( is_string( $this->sub_type ) ) {
			return $this->sub_type;
		} else {
			_doing_it_wrong( __METHOD__, __( 'Must set route sub type in subclass.', 'gk-gravityview' ), '2.0' );
			return '';
		}
	}

	/**
	 * Get a collection of items
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Get one item from the collection
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Create one item from the collection
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function create_item( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Update one item from the collection
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function update_item( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Delete one item from the collection
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function delete_item( $request ) {
		return $this->not_implemented();
	}


	/**
	 * Get a collection of items
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_sub_items( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Get one item from the collection
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_sub_item( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Create one item from the collection
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function create_sub_item( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Update one item from the collection for sub items
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function update_sub_item( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Delete one item from the collection for sub items
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function delete_sub_item( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function get_items_permissions_check( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Check if a given request has access to get a specific item
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function get_item_permissions_check( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Check if a given request has access to create items
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function create_item_permissions_check( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Check if a given request has access to update a specific item
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function update_item_permissions_check( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Check if a given request has access to delete a specific item
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function delete_item_permissions_check( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Prepare the item for create or update operation
	 *
	 * @todo ZACK - Use this as generic prepare to save or remove from usage.
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	protected function prepare_item_for_database( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Prepare the item for the REST response
	 *
	 *  @todo ZACK - Use this as generic prepare for response or remove from usage
	 *
	 * @since 2.0
	 * @param mixed            $item WordPress representation of the item.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		return $this->not_implemented();
	}

	/**
	 * Generic response for routes not yet implemented
	 *
	 * @since 2.0
	 * @return \WP_REST_Response
	 */
	protected function not_implemented() {
		$error = new \WP_Error( 'not-implemented-yet', __( 'Endpoint Not Yet Implemented.', 'gk-gravityview' ) );
		return new \WP_REST_Response( $error, 501 );
	}

	/**
	 * Fallback if subclass doesn't define routes
	 *
	 * Returns empty array for args instead of making an error.
	 *
	 * @since 2.0
	 * @param $method
	 * @return array
	 */
	public function __call( $method, $args ) {
		if ( in_array(
			$method,
			array(
				'create_item_args',
				'update_item_args',
				'create_sub_item_args',
				'update_sub_item_args',
			)
		) ) {
			return array();
		}
	}
}
