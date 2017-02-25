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
}
