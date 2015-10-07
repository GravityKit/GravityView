<?php
/**
 * The Entries route
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Josh Pollock <josh@joshpress.net>
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.14.4
 */
class GravityView_REST_Entries_Route extends GravityView_REST_Route {
	/**
	 * Route Name
	 *
	 * @since 1.14.4
	 *
	 * @access protected
	 * @string
	 */
	protected $route_name = 'entries';

	/**
	 * Sub type
	 *
	 * @since 1.14.4
	 * @access protected
	 * @var string
	 */
	protected $sub_type = 'field';

	/**
	 * Create an entry
	 *
	 * Callback for /v1/entries/
	 *
	 * @since 1.14.4
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function create_item( $request ) {
		$params = $request->get_params();
		//fix for https://github.com/WP-API/WP-API/issues/1621
		unset( $params[0] );
		unset( $params[1] );

		$item = $this->prepare_item_for_database( $request );
		$saved_item_id = true; //@todo GravityView Internal

		if ( ! is_wp_error( $saved_item_id)  && 0 != absint( $saved_item_id ) ) {
			return new WP_REST_Response( $saved_item_id, 200 );
		}


		return new WP_Error( 'cant-create', __( 'Can not create entry', 'gravity-view'), array( 'status' => 500 ) );

	}

	/**
	 * Update a view
	 *
	 * Callback for /v1/entries/{id}
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function update_item( $request ) {
		$url = $request->get_url_params();
		$entry_id = $url[ 'id' ];

		$params = $request->get_params();
		//fix for https://github.com/WP-API/WP-API/issues/1621
		unset( $params[0] );
		unset( $params[1] );

		$item = $this->prepare_item_for_database( $request );
		$saved_item_id = true; //@todo GravityView Internal

		if ( ! is_wp_error( $saved_item_id)  && 0 != absint( $saved_item_id ) ) {
			return new WP_REST_Response( $saved_item_id, 200 );
		}


		return new WP_Error( 'cant-create', __( 'Can not update entry', 'gravity-view'), array( 'status' => 500 ) );

	}

	/**
	 * Update a field value in entry
	 *
	 * Callback for /v1/entries/{id}/field/{id}/
	 *
	 * @since 1.14.4
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function update_sub_item( $request ) {
		$url = $request->get_url_params();
		$entry_id = $url[ 'id' ];
		$field_id = $url[ 's_id' ];

		$params = $request->get_params();
		//fix for https://github.com/WP-API/WP-API/issues/1621
		unset( $params[0] );
		unset( $params[1] );

		$item = $this->prepare_item_for_database( $request );
		$saved_item_id = true; //@todo GravityView Internal

		if ( ! is_wp_error( $saved_item_id)  && 0 != absint( $saved_item_id ) ) {
			return new WP_REST_Response( $saved_item_id, 200 );
		}


		return new WP_Error( 'cant-create', __( 'Can not update entry', 'gravity-view'), array( 'status' => 500 ) );

	}

	/**
	 * Arguments for the create entry route
	 *
	 * @since 1.14.4
	 * @return array
	 */
	public function create_item_args() {
		return array(
			'form_id' => array(
				'default'            => 0,
				'type'               => 'integer',
				'sanitize_callback'  => 'absint',
			),
			'id' => array(
				'default'            => 0,
				'sanitize_callback'  => 'absint',
			),
			'trigger_hooks' => array(
				'default'            => 0,
				'type'               => 'boolean',
				'validation_callback'  => array( $this, 'validate_boolean' ) ,
			),

		);
	}

	/**
	 * Arguments for the update entry route
	 *
	 * @since 1.14.4
	 * @return array
	 */
	public function update_item_args() {
		return array(
			'form_id' => array(
				'default'            => 0,
				'type'               => 'integer',
				'sanitize_callback'  => 'absint',
			),
			'value' => array(
				'type' => 'array',
				'default'            => 0,
			),
			'trigger_hooks' => array(
				'default'            => 0,
				'type'               => 'boolean',
				'validation_callback'  => array( $this, 'validate_boolean' ) ,
			),

		);
	}

	/**
	 * Arguments for the update feild route
	 *
	 * @since 1.14.4
	 * @return array
	 */
	public function update_sub_item_args() {
		return array(
			'value' => array(
				'type' => 'array',
				'default'            => 0,
			),
			'trigger_hooks' => array(
				'default'            => 0,
				'type'               => 'boolean',
				'validation_callback'  => array( $this, 'validate_boolean' ) ,
			),

		);
	}


}
