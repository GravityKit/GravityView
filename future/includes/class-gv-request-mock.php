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
		'is_view'       => false,
		'is_entry'      => false,
		'is_edit_entry' => false,
		'is_search'     => false,
		'get_arguments' => [],
	);

	public function is_view( $return_view = true ) {
		return $this->__call( __FUNCTION__, func_get_args() );
	}

	public function is_entry( $form_id = 0 ) {
		return $this->__call( __FUNCTION__, func_get_args() );
	}

	public function is_edit_entry( $form_id = 0 ) {
		return $this->__call( __FUNCTION__, func_get_args() );
	}

	public function is_search() {
		return $this->__call( __FUNCTION__, func_get_args() );
	}

	public function get_arguments(): array {
		return (array) $this->__call( __FUNCTION__, func_get_args() );
	}

	public function __call( $function, $args ) {
		return Utils::get( $this->returns, $function, null );
	}
}
