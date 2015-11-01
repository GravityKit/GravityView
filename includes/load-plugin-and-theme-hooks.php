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

// Plugins
include $include_path . 'class-gravityview-plugin-hooks-debug-bar.php';
include $include_path . 'class-gravityview-plugin-hooks-gravity-forms.php';
include $include_path . 'class-gravityview-plugin-hooks-yoast-seo.php';

// Themes
include $include_path . 'class-gravityview-theme-hooks-avada.php';
include $include_path . 'class-gravityview-theme-hooks-avia.php';
include $include_path . 'class-gravityview-theme-hooks-generatepress.php';
include $include_path . 'class-gravityview-theme-hooks-genesis.php';
