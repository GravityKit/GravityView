<?php
/**
 * Abstract class for creating GV REST API Routes from
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Josh Pollock <josh@joshpress.net>
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.14.4
 */
abstract class GravityView_REST_Route extends WP_REST_Controller {

	/**
	 * Route Name
	 *
	 * @since 1.14.4
	 * @access protected
	 * @var string
	 */
	protected $route_name;

	/**
	 * Sub type, forms {$namespace}/route_name/{id}/sub_type type endpoints
	 *
	 * @since 1.14.4
	 * @access protected
	 * @var string
	 */
	protected $sub_type;

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$namespace = GravityView_REST_Util::get_namespace();
		$base = $this->get_route_name();
		register_rest_route( $namespace, '/' . $base, array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'            => array(
					'page' => array(
						'default' => 1,
						'sanitize_callback' => 'absint'
					),
					'limit' => array(
						'default' => 10,
						'sanitize_callback' => 'absint'
					)
				)
			),
			array(
				'methods'         => WP_REST_Server::CREATABLE,
				'callback'        => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'            => $this->create_item_args()

			),
		) );
		register_rest_route( $namespace, '/' . $base . '/(?P<id>[\d]+)', array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'            => array(
					'context'          => array(
						'default'      => 'view',
					),
				),
			),
			array(
				'methods'         => WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'              => $this->update_item_args(),
			),
			array(
				'methods'  => WP_REST_Server::DELETABLE,
				'callback' => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'     => array(
					'force'    => array(
						'default'      => false,
					),
				),
			),
		) );

		$sub_type = $this->get_sub_type();

		register_rest_route( $namespace, '/' . $base . '/(?P<id>[\d]+)' . '/' . $sub_type , array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_sub_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'            => array(
					'args'            => array(
						'page' => array(
							'default' => 1,
							'sanitize_callback' => 'absint'
						),
						'limit' => array(
							'default' => 10,
							'sanitize_callback' => 'absint'
						)
					)
				)
			),
			array(
				'methods'         => WP_REST_Server::CREATABLE,
				'callback'        => array( $this, 'create_sub_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'     => $this->create_sub_item_args()
			),
		) );
		register_rest_route( $namespace, sprintf( '/%s/(?P<id>[\d]+)/%s/(?P<s_id>[\w-]+)', $base, $sub_type ) , array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_sub_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'            => array(
					'context'          => array(
						'default'      => 'view',
					),
				),
			),
			array(
				'methods'         => WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'update_sub_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'     => $this->update_sub_item_args()
			),
			array(
				'methods'  => WP_REST_Server::DELETABLE,
				'callback' => array( $this, 'delete_sub_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'     => array(
					'force'    => array(
						'default'      => false,
					),
				),
			),
		) );

	}

	/**
	 * Get route name
	 *
	 * MUST SET route_name property in subclass!
	 *
	 * @since 1.14.4
	 * @access protected
	 * @return string
	 */
	protected function get_route_name() {
		if( is_string( $this->route_name ) ) {
			return $this->route_name;
		}else{
			_doing_it_wrong( __METHOD__, __( 'Must set route name in subclass.', 'gravityview' ), '1.14.4' );
		}

	}

	/**
	 * Get sub_type
	 *
	 * MUST SET sub_type property in subclass!
	 *
	 * @since 1.14.4
	 * @access protected
	 * @return string
	 */
	protected function get_sub_type() {
		if( is_string( $this->sub_type ) ) {
			return $this->sub_type;
		}else{
			_doing_it_wrong( __METHOD__, __( 'Must set route sub type in subclass.', 'gravityview' ), '1.14.4' );
		}

	}

	/**
	 * Get a collection of items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Get one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		return $this->not_implemented();

	}

	/**
	 * Create one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function create_item( $request ) {
		return $this->not_implemented();

	}

	/**
	 * Update one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function update_item( $request ) {
		return $this->not_implemented();

	}

	/**
	 * Delete one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function delete_item( $request ) {
		return $this->not_implemented();

	}


	/**
	 * Get a collection of items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_sub_items( $request ) {
		return $this->not_implemented();

	}

	/**
	 * Get one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_sub_item( $request ) {
		return $this->not_implemented();

		//get parameters from request
		$params = $request->get_params();

	}

	/**
	 * Create one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function create_sub_item( $request ) {
		return $this->not_implemented();

	}

	/**
	 * Update one item from the collection for sub items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function update_sub_item( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Delete one item from the collection for sub items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function delete_sub_item( $request ) {
		return $this->not_implemented();
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
		return true;

	}

	/**
	 * Check if a given request has access to get a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to create items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function create_item_permissions_check( $request ) {
		return current_user_can( 'edit_something' );
	}

	/**
	 * Check if a given request has access to update a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to delete a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function delete_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Prepare the item for create or update operation
	 *
	 * @todo ZACK - Use this as genric prepare to save or remove from usage.
	 * @param WP_REST_Request $request Request object
	 * @return WP_Error|object $prepared_item
	 */
	protected function prepare_item_for_database( $request ) {
		return array();
	}

	/**
	 * Prepare the item for the REST response
	 *
	 *  @todo ZACK - Use this as genric prepare for response or remvoe from usage
	 *
	 * @since 1.14.4
	 * @param mixed $item WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return mixed
	 */
	public function prepare_item_for_response( $item, $request ) {
		return array();
	}


	/**
	 * Generic response for routes not yet implemented
	 *
	 * @since 1.14.4
	 * @return WP_REST_Response
	 */
	protected function not_implemented(  ) {
		$error = new WP_Error( 'not-implemented-yet', __( 'Endpoint Not Yet Implemented.', 'gravityview' )  );
		return new WP_REST_Response( $error, 501 );
	}


	/**
	 * Utility sanatizer for strings
	 *
	 * @todo get rid of this since it's not being used?
	 *
	 * @since 1.14.4
	 * @param mixed $value
	 * @return string
	 */
	public function safe_string( $value) {
		if( ! is_string( $value ) ) {
			return '';
		}

		return trim( strip_tags( $value ) );
	}

	/**
	 * Fallback if subclass doesn't define routes
	 *
	 * Returns empty array for args instead of making an error.
	 *
	 * @since 1.14.4
	 * @param $method
	 * @return array
	 */
	public function __call( $method, $args ) {
		if( in_array( $method, array(
			'create_item_args',
			'update_item_args',
			'create_sub_item_args',
			'update_sub_item_args'
		))) {
			return array();
		}

	}

	/**
	 * Ensure a boolen is a boolean
	 *
	 * @since 1.14.4
	 * @param $value
	 *
	 * @return bool
	 */
	public function validate_boolean( $value ) {
		if( in_array( $value, array( true, false, 'TRUE', 'FALSE', 'true', 'false', 1, 0, '1', '0' ) ) ){
			return true;
		}

	}


}
