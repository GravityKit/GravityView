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

class Views_Route extends Route {
	/**
	 * Route Name
	 *
	 * @since 2.0
	 *
	 * @access protected
	 * @string
	 */
	protected $route_name = 'views';

	/**
	 * Sub type, forms {$namespace}/route_name/{id}/sub_type type endpoints
	 *
	 * @since 2.0
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

		$items = \GVCommon::get_all_views( array(
			'posts_per_page' => $limit,
			'paged' => $page,
		) );

		if ( empty( $items ) ) {
			return new \WP_Error( 'gravityview-no-views', __( 'No Views found.', 'gravityview' ) ); //@todo message
		}

		$data = array(
			'views' => array(),
			'total' => wp_count_posts( 'gravityview' )->publish,
		);
		foreach ( $items as $item ) {
			$data['views'][] = $this->prepare_view_for_response( $item, $request );
		}

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Get one view
	 *
	 * Callback for /v1/views/{id}/
	 *
	 * @since 2.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {

		$url = $request->get_url_params();

		$view_id = intval( $url['id'] );

		$item = get_post( $view_id );

		//return a response or error based on some conditional
		if ( $item && ! is_wp_error( $item ) ) {
			$data = $this->prepare_view_for_response( $item, $request );

			return new \WP_REST_Response( $data, 200 );
		}else{
			return new \WP_Error( 'code', sprintf( 'A View with ID #%d was not found.', $view_id ) ); //@todo message
		}

	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @since 2.0
	 * @param \GV\View $view The view.
	 * @param \GV\Entry $entry WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @param string $context The context (directory, single)
	 * @return mixed The data that is sent.
	 */
	public function prepare_entry_for_response( $view, $entry, \WP_REST_Request $request, $context ) {
		$return = $entry->as_entry();

		// Only output the fields that should be displayed.
		$allowed = array();
		foreach ( $view->fields->by_visible()->by_position( "{$context}_*" )->all() as $field ) {
			$allowed[] = $field->ID;
		}

		/**
		 * @filter `gravityview/rest/entry/fields` Whitelist more entry fields that are output in regular REST requests.
		 * @param[in,out] array $allowed The allowed ones, default by_visible, by_position( "context_*" ), i.e. as set in the view.
		 * @param \GV\View $view The view.
		 * @param \GV\Entry $entry WordPress representation of the item.
		 * @param WP_REST_Request $request Request object.
		 * @param string $context The context (directory, single)
		 */
		$allowed = apply_filters( 'gravityview/rest/entry/fields', $allowed, $view, $entry, $request, $context );

		foreach ( $return as $key => $value ) {
			if ( ! in_array( $key, $allowed ) ) {
				unset( $return[ $key ] );
			}
		}

		// @todo Prepare the remaining values for display
		// @todo Set the labels!

		// Remove empty field values, saves lots of space.
		$return = array_filter( $return, 'gv_not_empty' );

		return $return;
	}

	/**
	 * Get entries from a view
	 *
	 * Callback for /v1/views/{id}/entries/
	 *
	 * @since 2.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_sub_items( $request ) {

		$url     = $request->get_url_params();
		$view_id = intval( $url['id'] );
		$format  = \GV\Utils::get( $url, 'format', 'json' );

		$view = \GV\View::by_id( $view_id );

		if ( $format == 'html' ) {
			$renderer = new \GV\View_Renderer();
			return new \WP_REST_Response( $renderer->render( $view, new Request( $request ) ), 200 );
		}

		$entries = $view->get_entries( new Request( $request ) );

		if ( ! $entries->all() ) {
			return new \WP_Error( 'gravityview-no-entries', __( 'No Entries found.', 'gravityview' ) );
		}

		$data = array( 'entries' => $entries->all(), 'total' => $entries->total() );

		foreach ( $data['entries'] as &$entry ) {
			$entry = $this->prepare_entry_for_response( $view, $entry, $request, 'directory' );
		}

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Get one entry from view
	 *
	 * Callback for /v1/views/{id}/entries/{id}/
	 *
	 * @uses GVCommon::get_entry
	 * @since 2.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_sub_item( $request ) {
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @since 2.0
	 * @param WP_Post $view_post WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return mixed
	 */
	public function prepare_view_for_response( $view_post, \WP_REST_Request $request ) {

		$view = \GV\View::from_post( $view_post );

		$item = $view->as_data();

		// Add all the WP_Post data
		$view_post = $view_post->to_array();

		unset( $view_post['to_ping'], $view_post['ping_status'], $view_post['pinged'], $view_post['post_type'], $view_post['filter'], $view_post['post_category'], $view_post['tags_input'], $view_post['post_content'], $view_post['post_content_filtered'] );

		$return = wp_parse_args( $item, $view_post );

		$return['title'] = $return['post_title'];

		$return['settings'] = isset( $return['atts'] ) ? $return['atts'] : array();
		unset( $return['atts'], $return['view_id'] );

		$return['search_criteria'] = array(
			'page_size' => rgars( $return, 'settings/page_size' ),
			'sort_field' => rgars( $return, 'settings/sort_field' ),
			'sort_direction' => rgars( $return, 'settings/sort_direction' ),
			'offset' => rgars( $return, 'settings/offset' ),
		);

		unset( $return['settings']['page_size'], $return['settings']['sort_field'], $return['settings']['sort_direction'] );

		return $return;
	}
}
