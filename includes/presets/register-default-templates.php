<?php
/**
 * GravityView default templates and generic template class
 *
 * @file register-default-templates.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15
 */

// Load default templates
add_action( 'init', 'gravityview_register_default_templates', 11 );

/**
 * Registers the default templates
 * @return void
 */
function gravityview_register_default_templates() {
	/** @define "GRAVITYVIEW_DIR" "../../" */

	// The abstract class required by all template files.
	require_once GRAVITYVIEW_DIR . 'includes/class-gravityview-template.php';

	$path = GRAVITYVIEW_DIR . 'includes/presets/';
	include_once $path . 'default-table/class-gravityview-default-template-table.php';
	include_once $path . 'default-list/class-gravityview-default-template-list.php';
	include_once $path . 'default-edit/class-gravityview-default-template-edit.php';
}
