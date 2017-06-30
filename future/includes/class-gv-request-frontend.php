<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The default frontend Request class.
 */
class Frontend_Request extends Request {
	/**
	 * Bootstrap.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
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
