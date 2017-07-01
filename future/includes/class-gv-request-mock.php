<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * A mock for testing.
 */
class Mock_Request extends Request {
	/**
	 * @var array The return values.
	 */
	public $returns = array(
		'is_view' => false,
		'is_edit_entry' => false,
		'is_edit' => false,
		'is_search' => false,
	);

	/**
	 * Bootstrap.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
	}

	public function __call( $function, $args ) {
		return rgar( $this->returns, $function, null );
	}
}
