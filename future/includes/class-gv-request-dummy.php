<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * A dummy implementation that merely tracks Views.
 */
final class Dummy_Request extends Frontend_Request {
	/**
	 * Bootstrap.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->views = new View_Collection();
	}

	/**
	 * A quick check against the current $post global.
	 *
	 * This is a dummy method and should not be used.
	 *  The Frontend_Request::is_view method should be used instead
	 *  and is ready in the `future-render` branch.
	 *
	 * @internal
	 *
	 * @return boolean The $post is a View or not.
	 */
	public function is_view() {
		global $post;
		return $post ? get_post_type( $post ) == 'gravityview' : false;
	}
}
