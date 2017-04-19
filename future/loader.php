<?php
/**
 * Just an early preloader for the future code.
 *
 * Compatible with all PHP versions syntax-wise.
 */

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
	/** @define "GRAVITYVIEW_DIR" "../" */
	require GRAVITYVIEW_DIR . 'future/gravityview.php';
}
