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
	include_once $path . 'business-listings/class-gravityview-preset-business-listings.php';
	include_once $path . 'business-data/class-gravityview-preset-business-data.php';
	include_once $path . 'profiles/class-gravityview-preset-profiles.php';
	include_once $path . 'staff-profiles/class-gravityview-preset-staff-profiles.php';
	include_once $path . 'website-showcase/class-gravityview-preset-website-showcase.php';
	include_once $path . 'issue-tracker/class-gravityview-preset-issue-tracker.php';
	include_once $path . 'resume-board/class-gravityview-preset-resume-board.php';
	include_once $path . 'job-board/class-gravityview-preset-job-board.php';
	include_once $path . 'event-listings/class-gravityview-preset-event-listings.php';
}