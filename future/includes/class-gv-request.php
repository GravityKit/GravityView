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
	public function __construct() {
	}

	/**
	 * Check if WordPress is_admin(), and make sure not DOING_AJAX.
	 *
	 * @return boolean
	 */
	public static function is_admin() {
		$doing_ajax = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;
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
		return ( self::is_ajax() && ! empty( $_POST['action'] ) && $_POST['action'] == 'parse-embed' && ! isset( $_POST['type'] ) );
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
	 * @todo tests
	 *
	 * @return \GV\View|false The view requested or false
	 */
	public function is_view() {
		global $post;
		if ( $post && get_post_type( $post ) == 'gravityview' ) {
			return \GV\View::from_post( $post );
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
	 * @return \GV\GF_Entry|false The entry requested or false.
	 */
	public function is_entry() {

		if ( $id = get_query_var( \GV\Entry::get_endpoint_name() ) ) {

			static $entries = array();

			if ( isset( $entries[ $id ] ) ) {
				return $entries[ $id ];
			}

			if ( $entry = \GV\GF_Entry::by_id( $id ) ) {
				$entries[ $id ] = $entry;
				return $entry;
			}

			$entries[ $id ] = false;
		}

		return false;
	}

	/**
	 * Checks whether this an edit entry request.
	 *
	 * @api
	 * @since 2.0
	 * @todo tests
	 *
	 * @return \GV\Entry|false The entry requested or false.
	 */
	public function is_edit_entry() {
		/**
		* @filter `gravityview_is_edit_entry` Whether we're currently on the Edit Entry screen \n
		* The Edit Entry functionality overrides this value.
		* @param boolean $is_edit_entry
		*/
		if ( ( $entry = $this->is_entry() ) && apply_filters( 'gravityview_is_edit_entry', false ) ) {
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

		$get = array_filter( $get, 'gravityview_is_not_empty_string' );

		if( $has_field_key = $this->_has_field_key( $get ) ) {
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
			if( empty( $field->_gf_field_class_name ) ) {
				$meta[] = preg_quote( $field->name );
			}
		}

		foreach ( $get as $key => $value ) {
			if ( preg_match('/^filter_(([0-9_]+)|'. implode( '|', $meta ) .')$/sm', $key ) ) {
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
