<?php
/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/** The future branch of GravityView requires PHP 5.3+ namespaces and SPL. */
if ( version_compare( phpversion(), '5.3' , '<' ) ) {
	return false;

/** Tests with a suppressed future. */
} else if ( defined( 'DOING_GRAVITYVIEW_TESTS' ) && getenv( 'GV_NO_FUTURE' ) ) {
	return false;

/** All looks fine. */
} else {
	/** @define "GRAVITYVIEW_DIR" "../" Require core and mocks */
	require GRAVITYVIEW_DIR . 'future/_mocks.php';
	require GRAVITYVIEW_DIR . 'future/includes/class-gv-core.php';

	/**
	 * The main GravityView wrapper function.
	 *
	 * Exposes classes and functionality via the \GV\Core instance.
	 *
	 * @api
	 * @since 2.0
	 *
	 * @return \GV\Core A global Core instance.
	 */
	function gravityview() {
		return \GV\Core::get();
	}

	/** Liftoff...*/
	gravityview();
}

/** Liftoff...*/
add_action( 'plugins_loaded', 'gravityview' );
