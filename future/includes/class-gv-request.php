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
	 * @todo load-(scripts|styles).php return true for \is_admin()!
	 *
	 * @return boolean
	 */
	public static function is_admin() {
		$doing_ajax = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;
		return is_admin() && ! $doing_ajax;
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
	 * Is this a REST request?
	 *
	 * @return boolean
	 */
	public static function is_rest() {
		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	/**
	 * The current $post is a View, no?
	 *
	 * @api
	 * @since future
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
	 * Is this an edit entry request?
	 *
	 * @api
	 * @since future
	 * @todo tests
	 *
	 * @return \GV\GF_Entry|false The entry requested or false.
	 */
	public function is_entry() {
		if ( $id = get_query_var( \GV\Entry::get_endpoint_name() ) ) {
			if ( $entry = \GV\GF_Entry::by_id( $id ) ) {
				return $entry;
			}
		}
		return false;
	}

	/**
	 * Check whether this an edit entry request.
	 *
	 * @api
	 * @since future
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
	 * Check whether this an entry search request.
	 *
	 * @api
	 * @since future
	 * @todo tests
	 * @todo implementation
	 *
	 * @return boolean True if this is a search request.
	 */
	public function is_search() {
		return $this->is_view() && ! empty ( $_GET['gv_search'] );
	}
}

/** Load implementations. */
require gravityview()->plugin->dir( 'future/includes/class-gv-request-frontend.php' );
require gravityview()->plugin->dir( 'future/includes/class-gv-request-admin.php' );
require gravityview()->plugin->dir( 'future/includes/class-gv-request-mock.php' );
