<?php
/**
 * PHP 5.2 class stubs. The core is not loaded but we need to
 * provide some classes that gracefully fail.
 */

class GV_Stub_Call {
	function __get( $_ ) {
		return new GV_Stub_Call();
	}

	function __call( $_, $__ ) {
	}
}

class GravityView_Extension {
	static $is_compatible = false;

	function __construct() {
	}

	function add_notice() {
	}
}

function gravityview() {
	return new GV_Stub_Call();
}
