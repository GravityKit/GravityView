<?php
/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) )
	die();

/** The future branch of GravityView requires PHP 5.3+ namespaces and SPL. */
if ( version_compare( phpversion(), '5.3' , '<' ) )
	return false;

/** Require core */
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
