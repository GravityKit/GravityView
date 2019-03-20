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
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
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
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item( $request ) {

		$url = $request->get_url_params();

		$view_id = intval( $url['id'] );

		$item = get_post( $view_id );

		//return a response or error based on some conditional
		if ( $item && ! is_wp_error( $item ) ) {
			$data = $this->prepare_view_for_response( $item, $request );
			return new \WP_REST_Response( $data, 200 );
		}

		return new \WP_Error( 'code', sprintf( 'A View with ID #%d was not found.', $view_id ) );
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @since 2.0
	 * @param \GV\View $view The view.
	 * @param \GV\Entry $entry WordPress representation of the item.
	 * @param \WP_REST_Request $request Request object.
	 * @param string $context The context (directory, single)
	 * @param string $class The value renderer. Default: null (raw value)
	 *
	 * @since 2.1 Add value renderer override $class parameter.
	 *
	 * @return mixed The data that is sent.
	 */
	public function prepare_entry_for_response( $view, $entry, \WP_REST_Request $request, $context, $class = null ) {

		// Only output the fields that should be displayed.
		$allowed = array();
		foreach ( $view->fields->by_position( "{$context}_*" )->by_visible()->all() as $field ) {
			$allowed[] = $field;
		}

		/**
		 * @filter `gravityview/rest/entry/fields` Whitelist more entry fields that are output in regular REST requests.
		 * @param[in,out] array $allowed The allowed ones, default by_visible, by_position( "context_*" ), i.e. as set in the view.
		 * @param \GV\View $view The view.
		 * @param \GV\Entry $entry The entry.
		 * @param \WP_REST_Request $request Request object.
		 * @param string $context The context (directory, single)
		 */
		$allowed_field_ids = apply_filters( 'gravityview/rest/entry/fields', wp_list_pluck( $allowed, 'ID' ), $view, $entry, $request, $context );

		$allowed = array_filter( $allowed, function( $field ) use ( $allowed_field_ids ) {
			return in_array( $field->ID, $allowed_field_ids, true );
		} );

		// Tack on additional fields if needed
		foreach ( array_diff( $allowed_field_ids, wp_list_pluck( $allowed, 'ID' ) ) as $field_id ) {
			$allowed[] = is_numeric( $field_id ) ? \GV\GF_Field::by_id( $view->form, $field_id ) : \GV\Internal_Field::by_id( $field_id );
		}

		$r = new Request( $request );
		$return = array();

		$renderer = new \GV\Field_Renderer();
		
		$used_ids = array();

		foreach ( $allowed as $field ) {
			$source = is_numeric( $field->ID ) ? $view->form : new \GV\Internal_Source();

			$field_id = $field->ID;
			$index = null;

			if ( ! isset( $used_ids[ $field_id ] ) ) {
				$used_ids[ $field_id ] = 0;
			} else {
				$index = ++$used_ids[ $field_id ];
			}

			if ( $index ) {
				/**
				 * Modify non-unique IDs (custom, id, etc.) to be unique and not gobbled up.
				 */
				$field_id = sprintf( '%s(%d)', $field_id, $index + 1 );
			}

			/**
			 * @filter `gravityview/api/field/key` Filter the key name in the results for JSON output.
			 * @param[in,out] string $field_id The ID. Should be unique or keys will be gobbled up.
			 * @param \GV\View $view The view.
			 * @param \GV\Entry $entry The entry.
			 * @param \WP_REST_Request $request Request object.
			 * @param string $context The context (directory, single)
			 */
			$field_id = apply_filters( 'gravityview/api/field/key', $field_id, $view, $entry, $request, $context );

			if ( ! $class && in_array( $field->ID, array( 'custom' ) ) ) {
				/**
				 * Custom fields (and perhaps some others) will require rendering as they don't
				 * contain an intrinsic value (for custom their value is stored in the view and requires a renderer).
				 * We force the CSV template to take over in such cases, it's good enough for most cases.
				 */
				$return[ $field_id ] = $renderer->render( $field, $view, $source, $entry, $r, '\GV\Field_CSV_Template' );
			} else if ( $class ) {
				$return[ $field_id ] = $renderer->render( $field, $view, $source, $entry, $r, $class );
			} else {
				switch ( $field->type ):
					case 'list':
						$return[ $field_id ] = unserialize( $field->get_value( $view, $source, $entry, $r ) );
						break;
					case 'fileupload':
					case 'business_hours':
						$return[ $field_id ] = json_decode( $field->get_value( $view, $source, $entry, $r ) );
						break;
					default;
						$return[ $field_id ] = $field->get_value( $view, $source, $entry, $r );
				endswitch;
			}
		}

		return $return;
	}

	/**
	 * Get entries from a view
	 *
	 * Callback for /v1/views/{id}/entries/
	 *
	 * @since 2.0
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_sub_items( $request ) {

		$url     = $request->get_url_params();
		$view_id = intval( $url['id'] );
		$format  = \GV\Utils::get( $url, 'format', 'json' );

		if( $post_id = $request->get_param('post_id') ) {
			global $post;

			$post = get_post( $post_id );

			if ( ! $post || is_wp_error( $post ) ) {
				return new \WP_Error( 'gravityview-post-not-found', sprintf( 'A post with ID #%d was not found.', $post_id ) );
			}

			$collection = \GV\View_Collection::from_post( $post );

			if ( ! $collection->contains( $view_id ) ) {
				return new \WP_Error( 'gravityview-post-not-contains', sprintf( 'The post with ID #%d does not contain a View with ID #%d', $post_id, $view_id ) );
			}
		}

		$view = \GV\View::by_id( $view_id );

		if ( 'html' === $format ) {

			$renderer = new \GV\View_Renderer();
			$count = $total = 0;

			/** @var \GV\Template_Context $context */
			add_action( 'gravityview/template/view/render', function( $context ) use ( &$count, &$total ) {
				$count = $context->entries->count();
				$total = $context->entries->total();
			} );

			$output = $renderer->render( $view, new Request( $request ) );

			/**
			 * @filter `gravityview/rest/entries/html/insert_meta` Whether to include `http-equiv` meta tags in the HTML output describing the data
			 * @since 2.0
			 * @param bool $insert_meta Add <meta> tags? [Default: true]
			 * @param int $count The number of entries being rendered
			 * @param \GV\View $view The view.
			 * @param \WP_REST_Request $request Request object.
			 * @param int $total The number of total entries for the request
			 */
			$insert_meta = apply_filters( 'gravityview/rest/entries/html/insert_meta', true, $count, $view, $request, $total );

			if ( $insert_meta ) {
				$output = '<meta http-equiv="X-Item-Count" content="' . $count . '" />' . $output;
				$output = '<meta http-equiv="X-Item-Total" content="' . $total . '" />' . $output;
			}

			$response = new \WP_REST_Response( $output, 200 );
			$response->header( 'X-Item-Count', $count );
			$response->header( 'X-Item-Total', $total );

			return $response;
		}

		$entries = $view->get_entries( new Request( $request ) );

		if ( ! $entries->all() ) {
			return new \WP_Error( 'gravityview-no-entries', __( 'No Entries found.', 'gravityview' ) );
		}

		if ( 'csv' === $format ) {
			ob_start();

			$csv = fopen( 'php://output', 'w' );

			/** Da' BOM :) */
			if ( apply_filters( 'gform_include_bom_export_entries', true, $view->form ? $view->form->form : null ) ) {
				fputs( $csv, "\xef\xbb\xbf" );
			}

			$headers_done = false;

			foreach ( $entries->all() as $entry ) {
				$entry = $this->prepare_entry_for_response( $view, $entry, $request, 'directory', '\GV\Field_CSV_Template' );

				if ( ! $headers_done ) {
					$headers_done = fputcsv( $csv, array_map( array( '\GV\Utils', 'strip_excel_formulas' ), array_keys( $entry ) ) );
				}

				fputcsv( $csv, array_map( array( '\GV\Utils', 'strip_excel_formulas' ), $entry ) );
			}

			$response = new \WP_REST_Response( '', 200 );
			$response->header( 'X-Item-Count', $entries->count() );
			$response->header( 'X-Item-Total', $entries->total() );
			$response->header( 'Content-Type', 'text/csv' );

			fflush( $csv );

			$data = rtrim( ob_get_clean() );

			add_filter( 'rest_pre_serve_request', function() use ( $data ) {
				echo $data;
				return true;
			} );

			if ( defined( 'DOING_GRAVITYVIEW_TESTS' ) && DOING_GRAVITYVIEW_TESTS ) {
				echo $data; // rest_pre_serve_request is not called in tests
			}

			return $response;
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
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_sub_item( $request ) {
		$url      = $request->get_url_params();
		$view_id  = intval( $url['id'] );
		$entry_id = intval( $url['s_id'] );
		$format   = \GV\Utils::get( $url, 'format', 'json' );

		$view  = \GV\View::by_id( $view_id );
		$entry = \GV\GF_Entry::by_id( $entry_id );

		if ( $format === 'html' ) {
			$renderer = new \GV\Entry_Renderer();
			return $renderer->render( $entry, $view, new Request( $request ) );
		}

		return $this->prepare_entry_for_response( $view, $entry, $request, 'single' );
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @since 2.0
	 * @param \WP_Post $view_post WordPress representation of the item.
	 * @param \WP_REST_Request $request Request object.
	 * @return mixed
	 */
	public function prepare_view_for_response( $view_post, \WP_REST_Request $request ) {
		if ( is_wp_error( $this->get_item_permissions_check( $request, $view_post->ID ) ) ) {
			// Redacted out view.
			return array( 'ID' => $view_post->ID, 'post_content' => __( 'You are not allowed to access this content.', 'gravityview' ) );
		}

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

		// Redact for non-logged ins
		if ( ! \GVCommon::has_cap( 'edit_others_gravityviews' ) ) {
			unset( $return['settings'] );
			unset( $return['search_criteria'] );
		}
		
		if ( ! \GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
			unset( $return['form'] );
		}

		return $return;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		if ( func_num_args() === 2 ) {
			$view_id = func_get_arg( 1 ); // $view_id override
		} else {
			$url     = $request->get_url_params();
			$view_id = intval( $url['id'] );
		}

		if ( ! $view = \GV\View::by_id( $view_id ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'You are not allowed to access this content.', 'gravityview' ) );
		}

		while ( $error = $view->can_render( array( 'rest' ), $request ) ) {

			if ( ! is_wp_error( $error ) ) {
				break;
			}

			switch ( str_replace( 'gravityview/', '', $error->get_error_code() ) ) {
				case 'rest_disabled':
				case 'post_password_required':
				case 'not_public':
				case 'embed_only':
				case 'no_direct_access':
					return new \WP_Error( 'rest_forbidden', __( 'You are not allowed to access this content.', 'gravityview' ) );
				case 'no_form_attached':
					return new \WP_Error( 'rest_forbidden', __( 'This View is not configured properly.', 'gravityview' ) );
				default:
					return new \WP_Error( 'rest_forbidden', __( 'You are not allowed to access this content.', 'gravityview' ) );
			}
		}

		/**
		 * @filter `gravityview/view/output/rest` Disable rest output. Final chance.
		 * @param[in,out] bool Enable or not.
		 * @param \GV\View $view The view.
		 */
		if ( ! apply_filters( 'gravityview/view/output/rest', true, $view ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'You are not allowed to access this content.', 'gravityview' ) );
		}

		return true;
	}

	public function get_sub_item_permissions_check( $request ) {
		// Accessing a single entry needs the View access permissions.
		if ( is_wp_error( $error = $this->get_items_permissions_check( $request ) ) ) {
			return $error;
		}

		$url     = $request->get_url_params();
		$view_id = intval( $url['id'] );
		$entry_id = intval( $url['s_id'] );

		$view = \GV\View::by_id( $view_id );

		if ( ! $entry = \GV\GF_Entry::by_id( $entry_id ) ) {
			return new \WP_Error( 'rest_forbidden', 'You are not allowed to view this content.', 'gravityview' );
		}

		if ( $entry['form_id'] != $view->form->ID ) {
			return new \WP_Error( 'rest_forbidden', 'You are not allowed to view this content.', 'gravityview' );
		}

		if ( $entry['status'] != 'active' ) {
			return new \WP_Error( 'rest_forbidden', 'You are not allowed to view this content.', 'gravityview' );
		}

		if ( apply_filters( 'gravityview_custom_entry_slug', false ) && $entry->slug != get_query_var( \GV\Entry::get_endpoint_name() ) ) {
			return new \WP_Error( 'rest_forbidden', 'You are not allowed to view this content.', 'gravityview' );
		}

		$is_admin_and_can_view = $view->settings->get( 'admin_show_all_statuses' ) && \GVCommon::has_cap('gravityview_moderate_entries', $view->ID );

		if ( $view->settings->get( 'show_only_approved' ) && ! $is_admin_and_can_view ) {
			if ( ! \GravityView_Entry_Approval_Status::is_approved( gform_get_meta( $entry->ID, \GravityView_Entry_Approval::meta_key ) )  ) {
				return new \WP_Error( 'rest_forbidden', 'You are not allowed to view this content.', 'gravityview' );
			}
		}

		return true;
	}

	public function get_items_permissions_check( $request ) {
		// Getting a list of all Views is always possible.
		return true;
	}

	public function get_sub_items_permissions_check( $request ) {
		// Accessing all entries of a View needs the same permissions as accessing the View.
		return $this->get_item_permissions_check( $request );
	}
}
