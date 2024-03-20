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

require_once GRAVITYVIEW_DIR . 'vendor/autoload.php';
require_once GRAVITYVIEW_DIR . 'vendor_prefixed/autoload.php';

GravityKit\GravityView\Foundation\Core::register( GRAVITYVIEW_FILE );

/** @define "GRAVITYVIEW_DIR" "../" */
require GRAVITYVIEW_DIR . 'future/gravityview.php';
