<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The default Request class.
 *
 * Finds out what Views are being requested.
 */
class Frontend_Request extends Request {
	/**
	 * Bootstrap.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'parse' ), 12 );
		parent::__construct();
	}

	/**
	 * Parse the current WordPress context around the $post global.
	 *
	 * Called by the `wp` hook.
	 *
	 * @param \WP $wp The WordPress environment class. Unused.
	 *
	 * @internal
	 * @return void
	 */
	public function parse( $wp = null ) {
		/** Nothing to do in an administrative context. */
		if ( $this->is_admin() ) {
			return;
		}

		/** The post might either be a gravityview, or contain gravityview shortcodes. */
		global $post;

		if ( ! $post instanceof \WP_Post ) {
			$this->views = new View_Collection();
		} else {
			$this->views = View_Collection::from_post( $post );
		}
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
