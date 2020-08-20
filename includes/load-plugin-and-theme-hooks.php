<?php
/**
 * Include files that load plugin and theme hooks
 *
 * @file      load-plugin-and-theme-hooks.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15.2
 */


/** @define "GRAVITYVIEW_DIR" "../" */
$include_path = GRAVITYVIEW_DIR . 'includes/plugin-and-theme-hooks/';

// Abstract class
require $include_path . 'abstract-gravityview-plugin-and-theme-hooks.php';

$glob_flags = defined( 'GLOB_BRACE' ) ? GLOB_BRACE : 0;

$plugin_theme_hooks_files = glob( $include_path . 'class-gravityview-{plugin,theme}-hooks-*.php', $glob_flags );

// Load all plugin and theme files automatically
foreach ( (array) $plugin_theme_hooks_files as $gv_hooks_filename ) {
	include $gv_hooks_filename;
}
