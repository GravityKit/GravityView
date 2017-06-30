<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The default Dashboard Request class.
 */
class Admin_Request extends Request {
	/**
	 * Bootstrap.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
	}
}
