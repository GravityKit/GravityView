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

/**
 * Do not allow activation if PHP version is lower than 5.3.
 */
register_activation_hook( GRAVITYVIEW_DIR . 'gravityview.php', 'gravityview_lock_version' );
function gravityview_lock_version() {
	$version = phpversion();
	if ( version_compare( $version, '5.3', '<' ) ) {

		if ( php_sapi_name() == 'cli' ) {
			printf( __( "GravityView requires PHP Version %s or newer. You're using Version %s. Please ask your host to upgrade your server's PHP.", 'gravityview' ),
				GV_MIN_PHP_VERSION, phpversion() );
		} else {
			printf( '<body style="padding: 0; margin: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen-Sans, Ubuntu, Cantarell, \'Helvetica Neue\', sans-serif;">' );
			printf( '<img src="' . plugins_url( 'assets/images/astronaut-200x263.png', GRAVITYVIEW_FILE ) . '" alt="The GravityView Astronaut Says:" style="float: left; height: 60px; margin-right : 10px;" />' );
			printf( __( "%sGravityView requires PHP Version %s or newer.%s \n\nYou're using Version %s. Please ask your host to upgrade your server's PHP.", 'gravityview' ),
				'<h3 style="font-size:16px; margin: 0 0 8px 0;">', GV_MIN_PHP_VERSION , "</h3>\n\n", $version );
			printf( '</body>' );
		}

		exit; /** Die without activating. Sorry. */
	}
}

/** The future branch of GravityView requires PHP 5.3+ namespaces and SPL. */
if ( version_compare( phpversion(), '5.3.0' , '<' ) ) {
	require GRAVITYVIEW_DIR . 'future/_stubs.php';

/** All looks fine. */
} else {
	/** @define "GRAVITYVIEW_DIR" "../" */
	require GRAVITYVIEW_DIR . 'future/gravityview.php';
}
