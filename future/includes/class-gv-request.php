<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The Request abstract class.
 *
 * Knows more about the request than anyone else.
 */
abstract class Request {

	public function __construct() {}

	/**
	 * Whether this request is something that is renderable.
	 *
	 * @since 2.5.2
	 *
	 * @return bool Yes or no.
	 */
	public function is_renderable() {

		$is_renderable = in_array(
			get_class( $this ),
			array(
				'GV\Frontend_Request',
				'GV\Mock_Request',
				'GV\REST\Request',
			),
			true
		);

		/**
		 * Is this request renderable?
		 *
		 * @since 2.5.2
		 *
		 * @param bool        $is_renderable Whether the request is renderable.
		 * @param \GV\Request $request       The Request object.
		 */
		return apply_filters( 'gravityview/request/is_renderable', $is_renderable, $this );
	}

	/**
	 * Check if WordPress is_admin(), and make sure not DOING_AJAX.
	 *
	 * @return boolean
	 */
	public static function is_admin() {
		$doing_ajax          = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;
		$load_scripts_styles = preg_match( '#^/wp-admin/load-(scripts|styles).php$#', Utils::_SERVER( 'SCRIPT_NAME' ) );

		return is_admin() && ! ( $doing_ajax || $load_scripts_styles );
	}

	/**
	 * This is the frontend.
	 *
	 * @return boolean True or false.
	 */
	public static function is_frontend() {
		return ! is_admin();
	}

	/**
	 * Is this the Add Media / From URL preview request?
	 *
	 * Will not work in WordPress 4.8+
	 *
	 * @return boolean
	 */
	public static function is_add_oembed_preview() {
		/** The preview request is a parse-embed AJAX call without a type set. */
		return ( self::is_ajax() && ! empty( $_POST['action'] ) && 'parse-embed' == $_POST['action'] && ! isset( $_POST['type'] ) );
	}

	/**
	 * Is this an AJAX call in progress?
	 *
	 * @return boolean
	 */
	public static function is_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	/**
	 * Is this a REST request? Call after parse_request.
	 *
	 * @return boolean
	 */
	public static function is_rest() {
		return ! empty( $GLOBALS['wp']->query_vars['rest_route'] );
	}

	/**
	 * The current $post is a View, no?
	 *
	 * @api
	 * @since 2.0
	 * @since 2.16 Added $return_view parameter.
	 *
	 * @param bool $return_view Whether to return a View object or boolean.
	 *
	 * @return \GV\View|bool If the global $post is a View, returns the View or true, depending on $return_view. If not a View, returns false.
	 */
	public function is_view( $return_view = true ) {
		global $post;
		if ( $post && 'gravityview' === get_post_type( $post ) ) {
			return ( $return_view ) ? \GV\View::from_post( $post ) : true;
		}
		return false;
	}

	/**
	 * Checks whether this is a single entry request
	 *
	 * @api
	 * @since 2.0
	 * @todo tests
	 *
	 * @param int $form_id The form ID, since slugs can be non-unique. Default: 0.
	 *
	 * @return \GV\GF_Entry|false The entry requested or false.
	 */
	public function is_entry( $form_id = 0 ) {
		global $wp_query;

		if ( ! $wp_query ) {
			return false;
		}

		$id = get_query_var( Entry::get_endpoint_name() );

		if ( ! $id ) {
			return false;
		}

		static $entries = array();

		if ( isset( $entries[ "$form_id:$id" ] ) ) {
			return $entries[ "$form_id:$id" ];
		}

		$view = $this->is_view();

		/**
		 * Not CPT, so probably a shortcode
		 */
		if ( ! $view ) {
			$view = gravityview()->views->get();
		}

		// If there are multiple Views on a page, the permalink _should_ include `gvid` to specify which View to use.
		if ( $view instanceof \GV\View_Collection ) {
			$gvid = \GV\Utils::_GET( 'gvid' );
			$view = $view->get( $gvid );
		}

		/**
		 * A joined request.
		 */
		if ( $view instanceof \GV\View && ( $joins = $view->joins ) ) {
			$forms       = array_merge( wp_list_pluck( $joins, 'join' ), wp_list_pluck( $joins, 'join_on' ) );
			$valid_forms = array_unique( wp_list_pluck( $forms, 'ID' ) );

			$multientry = array();
			foreach ( $ids = explode( ',', $id ) as $i => $id ) {

				$valid_form = \GV\Utils::get( $valid_forms, $i, 0 );

				if ( ! $e = GF_Entry::by_id( $id, $valid_form ) ) {
					return false;
				}

				if ( ! in_array( $e['form_id'], $valid_forms ) ) {
					return false;
				}

				array_push( $multientry, $e );
			}

			// Allow Edit Entry to only edit a single entry on a multi-entry
			$is_edit_entry = apply_filters( 'gravityview_is_edit_entry', false );

			// Edit entry links are single-entry based
			if ( $is_edit_entry && 1 !== count( $multientry ) ) {
				return false;
			}

			$entry = Multi_Entry::from_entries( array_filter( $multientry ) );
		} else {
			/**
			 * A regular one.
			 */
			$entry = GF_Entry::by_id( $id, $form_id );
		}

		$entries[ "$form_id:$id" ] = $entry;

		return $entry;
	}

	/**
	 * Checks whether this an edit entry request.
	 *
	 * @api
	 * @since 2.0
	 * @todo tests
	 *
	 * @param int $form_id The form ID, since slugs can be non-unique. Default: 0.
	 *
	 * @return \GV\Entry|false The entry requested or false.
	 */
	public function is_edit_entry( $form_id = 0 ) {
		$entry = $this->is_entry( $form_id );

		/**
		 * Checks whether we're currently on the Edit Entry screen.
		 *
		 * The Edit Entry functionality overrides this value.
		 *
		 * @since 2.0-beta.2
		 *
		 * @param bool $is_edit_entry Whether the current request is an Edit Entry request. Default: false.
		 */
		if ( $entry && apply_filters( 'gravityview_is_edit_entry', false ) ) {
			if ( $entry->is_multi() ) {
				return reset( $entry->entries );
			}

			return $entry;
		}

		return false;
	}

	/**
	 * Checks whether this an entry search request.
	 *
	 * @api
	 * @since 2.0
	 * @todo implementation
	 *
	 * @return boolean True if this is a search request.
	 */
	public function is_search() {

		$search_method = apply_filters( 'gravityview/search/method', 'get' );

		if ( 'post' === $search_method ) {
			$get = $_POST;
		} else {
			$get = $_GET;
		}

		unset( $get['mode'] );

		$get = array_filter( (array) $get, 'gravityview_is_not_empty_string' );

		if ( $this->_has_field_key( $get ) ) {
			return true;
		}

		return isset( $get['gv_search'] ) || isset( $get['gv_start'] ) || isset( $get['gv_end'] ) || isset( $get['gv_by'] ) || isset( $get['gv_id'] );
	}

	/**
	 * Calculate whether the $_REQUEST has a GravityView field
	 *
	 * @internal
	 * @todo Roll into the future Search refactor
	 *
	 * @since 2.0.7
	 *
	 * @param array $get $_POST or $_GET array
	 *
	 * @return bool True: GravityView-formatted field detected; False: not detected
	 */
	private function _has_field_key( $get ) {

		$has_field_key = false;

		$fields = \GravityView_Fields::get_all();

		$meta = array();
		foreach ( $fields as $field ) {
			if ( empty( $field->_gf_field_class_name ) ) {
				$meta[] = preg_quote( $field->name );
			}
		}

		foreach ( $get as $key => $value ) {
			if ( preg_match( '/^(filter|input)_(([0-9_]+)|' . implode( '|', $meta ) . ')$/sm', $key ) ) {
				$has_field_key = true;
				break;
			}
		}

		return $has_field_key;
	}
}

/** Load implementations. */
require gravityview()->plugin->dir( 'future/includes/class-gv-request-frontend.php' );
require gravityview()->plugin->dir( 'future/includes/class-gv-request-admin.php' );
require gravityview()->plugin->dir( 'future/includes/rest/class-gv-request-rest.php' );
require gravityview()->plugin->dir( 'future/includes/class-gv-request-mock.php' );
