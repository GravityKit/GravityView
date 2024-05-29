<?php
/**
 * Include files that load plugin and theme hooks
 *
 * @file      load-plugin-and-theme-hooks.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15.2
 */

/** @define "GRAVITYVIEW_DIR" "../" */
$include_path = GRAVITYVIEW_DIR . 'includes/plugin-and-theme-hooks/';

// Abstract class
require $include_path . 'abstract-gravityview-plugin-and-theme-hooks.php';
require $include_path . 'class-gravityview-object-placeholder.php';
require $include_path . 'class-gravityview-feature-upgrade.php';

$plugin_hooks_files = glob( $include_path . 'class-gravityview-plugin-hooks-*.php' );

// Load all plugin files automatically
foreach ( (array) $plugin_hooks_files as $plugin_hooks_file ) {
	include $plugin_hooks_file;
}

$theme_hooks_files = glob( $include_path . 'class-gravityview-theme-hooks-*.php' );

// Load all theme files automatically
foreach ( (array) $theme_hooks_files as $theme_hooks_file ) {
	include $theme_hooks_file;
}
