<?php
/**
 * The Views route
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Josh Pollock <josh@joshpress.net>
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.14.4
 */
class GravityView_REST_Views_Route extends GravityView_REST_Route {

	/**
	 * Route Name
	 *
	 * @since 1.14.4
	 *
	 * @access protected
	 * @string
	 */
	protected $route_name = 'views';

	/**
	 * Sub type, forms {$namespace}/route_name/{id}/sub_type type endpoints
	 *
	 * @since 1.14.4
	 * @access protected
	 * @var string
	 */
	protected $sub_type = 'entries';


	/**
	 * Get a collection of views
	 *
	 * Callback for GET /v1/views/
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {

		$page = $request->get_param( 'page' );
		$limit = $request->get_param( 'limit' );

		// @todo GravityView internal
		$items = GVCommon::get_all_views( array(
			'posts_per_page' => $limit,
			'paged' => $page,
		));

		if( empty( $items ) ) {
			return new WP_Error( 'gravityview-no-views', __( 'No views found.', 'gravityview' ) ); //@todo message
		}

		$data = array();
		foreach( $items as $item ) {
			$data[] = $this->prepare_view_for_response( $item, $request );
		}

		return new WP_REST_Response( $data, 200 );

	}

	/**
	 * Get one view
	 *
	 * Callback for /v1/views/{id}/
	 *
	 * @since 1.14.4
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {

		$url = $request->get_url_params();

		$view_id = intval( $url['id'] );

		$item = get_post( $view_id );

		//return a response or error based on some conditional
		if ( ! is_wp_error( $item ) ) {
			$data = $this->prepare_view_for_response( $item, $request );
			return new WP_REST_Response( $data, 200 );
		}else{
			return new WP_Error( 'code', __( 'Fail Message', 'gravityview' ) ); //@todo message
		}

	}

	/**
	 * Prepare the item for the REST response
	 *
	 *  @todo ZACK - Use this as generic prepare for response or remove from usage
	 *
	 * @since 1.14.4
	 * @param mixed $item WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return mixed
	 */
	public function prepare_entry_for_response( $item, WP_REST_Request $request ) {

		$return = $item;

		// Remove empty field values, saves lots of space.
		$return = array_filter( $return, 'gv_not_empty' );

		return $return;
	}

	/**
	 * Get entries from a view
	 *
	 * Callback for /v1/views/{id}/entries/
	 *
	 * @since 1.14.4
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_sub_items( $request ) {

		$url     = $request->get_url_params();
		$view_id = intval( $url['id'] );
		$page    = $request->get_param( 'page' );
		$limit   = GravityView_frontend::calculate_page_size( $request->get_param( 'limit' ) );
		$offset  = GravityView_frontend::calculate_offset( $limit, $page );

		$form_id = gravityview_get_form_id( $view_id );

		$atts = array(
			'id'        => $view_id,
			'page_size' => $limit,
			'offset'    => $offset,
			'cache'     => false
		);

		GravityView_frontend::getInstance()->set_context_view_id( $view_id );

		$data = GravityView_frontend::get_view_entries( $atts, $form_id );

		if ( empty( $data ) ) {
			return new WP_Error( 'gravityview-no-views', __( 'No Views found.', 'gravityview' ) ); //@todo message
		}

		foreach ( $data['entries'] as &$entry ) {
			$entry = $this->prepare_entry_for_response( $entry, $request );
		}

		return new WP_REST_Response( $data, 200 );

	}

	/**
	 * Get one entry from view
	 *
	 * Callback for /v1/views/{id}/entries/{id}/
	 *
	 * @uses GVCommon::get_entry
	 * @since 1.14.4
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_sub_item( $request ) {

		$url = $request->get_url_params();
		$view_id = $url['id'];
		$entry_slug_or_id = $url['s_id'];

		/**
		 * We need to set some information so that the search criteria is properly fetched.
		 * This allows filtering out entries that shouldn't be included in the View results.
		 * @see GVCommon::check_entry_display()
		 * @see GVCommon::calculate_get_entries_criteria
		 */
		GravityView_frontend::getInstance()->set_context_view_id( $view_id );
		GravityView_frontend::getInstance()->setIsGravityviewPostType( true );
		GravityView_frontend::getInstance()->setSingleEntry( $entry_slug_or_id );

		$item = GVCommon::get_entry( $entry_slug_or_id, true, true );

		//return a response or error based on some conditional
		if ( $item && ! is_wp_error( $item ) ) {

			$data = $this->prepare_entry_for_response( $item, $request );

			return new WP_REST_Response( $data, 200 );
		}else{
			return new WP_Error( 'code', __( 'Fail Message', 'gravityview' ) ); //@todo message
		}

	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @todo ZACK - Use this as genric prepare for response or remvoe from usage
	 *
	 * @since 1.14.4
	 * @param mixed $item WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return mixed
	 */
	public function prepare_view_for_response( $view_post, $request ) {

		$view_id = $view_post->ID;

		$item = GravityView_View_Data::getInstance()->get_view( $view_id );

		// Add all the WP_Post data
		$view_post = $view_post->to_array();

		unset( $view_post['to_ping'], $view_post['ping_status'], $view_post['pinged'], $view_post['post_type'], $view_post['filter'], $view_post['post_category'], $view_post['tags_input'], $view_post['post_content'], $view_post['post_content_filtered'] );


		$return = wp_parse_args( $item, $view_post );

		/*$return['form_id'] = gravityview_get_form_id( $view_id );
		$return['form'] = ! empty( $return['form_id'] ) ? gravityview_get_form( $return['form_id'] ) : array();
		$return['template_id'] = gravityview_get_template_id( $view_id );
		$return['fields'] = GravityView_View_Data::getInstance()->get_fields( $view_id );
		$return['widgets'] = get_post_meta( $view_id, '_gravityview_directory_widgets', true );*/

		$return['title'] = $return['post_title'];

		// TODO: Configure layout

		// TODO: Configure widget output to match spec
		// https://docs.google.com/document/d/1n8nB96EK4zCMN9AE8FEzK5SiVG77Slfzkcj4JtSdZNE/edit#
		$return['widgets']['above'] = rgars( $return, 'widgets/header_top' );

		$return['settings'] = isset( $return['atts'] ) ? $return['atts'] : array();
		unset( $return['atts'], $return['view_id'] );

		$return['search_criteria'] = array(
			'page_size' => rgars( $return, 'settings/page_size' ),
			'sort_field' => rgars( $return, 'settings/sort_field' ),
			'sort_direction' => rgars( $return, 'settings/sort_direction' ),
			'offset' => intval( $request->get_param('offset') ),
		);

		unset( $return['settings']['page_size'], $return['settings']['sort_field'], $return['settings']['sort_direction'] );

		return $return;
	}
}
