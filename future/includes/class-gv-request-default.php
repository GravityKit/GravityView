<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) )
	die();

/**
 * The default Request class.
 *
 * Parses and transforms an end-request for a view to a View
 *  in a default frontend, WP_Query-based WordPress context.
 */
final class DefaultRequest extends Request {
	/**
	 * Bootstrap.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->views = new ViewList();
		add_action( 'wp', array( $this, 'parse' ) );
	}

	/**
	 * Parse the current WordPress context.
	 *
	 * Called by the `wp` hook.
	 *
	 * @param \WP $wp The WordPress environment class. Unused.
	 *
	 * @return void
	 */
	public function parse( $wp = null ) {
	}

	/**
	 * Check if WordPress is_admin(), and make sure not DOING_AJAX.
	 *
	 * @todo load-(scripts|styles).php return true for \is_admin()!
	 *
	 * @api
	 * @since future
	 *
	 * @return bool Pure is_admin or not?
	 */
	public function is_admin() {
		$doing_ajax = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;
		return is_admin() && ! $doing_ajax;
	}
}
