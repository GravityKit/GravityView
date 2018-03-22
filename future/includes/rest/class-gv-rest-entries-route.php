<?php
/**
 * @package   GravityView
 * @license   GPL2+
 * @author    Josh Pollock <josh@joshpress.net>
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 2.0
 */
namespace GV\REST;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

class Entries_Route extends Route {
	/**
	 * Route Name
	 *
	 * @since 2.0
	 *
	 * @access protected
	 * @string
	 */
	protected $route_name = 'entries';

	/**
	 * Sub type
	 *
	 * @since 2.0
	 * @access protected
	 * @var string
	 */
	protected $sub_type = 'field';

	/**
	 * Create an entry
	 *
	 * Callback for /v1/entries/
	 *
	 * @since 2.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function create_item( $request ) {
		$params = $request->get_params();
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
	 * @since 2.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function update_sub_item( $request ) {
		$url = $request->get_url_params();
		$entry_id = $url[ 'id' ];
		$field_id = $url[ 's_id' ];

		$params = $request->get_params();
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
	 * @since 2.0
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
	 * @since 2.0
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
	 * @since 2.0
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
