<?php
/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/** Require core and mocks */

/** @define "GRAVITYVIEW_DIR" "../" */
require GRAVITYVIEW_DIR . 'future/_mocks.php';
require GRAVITYVIEW_DIR . 'future/includes/class-gv-core.php';

/** T-minus 3... 2.. 1... */
\GV\Core::bootstrap();

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
add_action( 'plugins_loaded', 'gravityview' );
