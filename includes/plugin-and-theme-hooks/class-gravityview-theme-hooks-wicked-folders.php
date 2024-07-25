<?php
/**
 * Add Wicked Folders compatibility to GravityView
 *
 * @file      class-gravityview-theme-hooks-generatepress.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2020, Katz Web Services, Inc.
 */

/**
 * @inheritDoc
 */
class GravityView_Plugin_Hooks_Wicked_Folders extends GravityView_Plugin_and_Theme_Hooks {

	protected $class_name = 'Wicked_Folders';

	protected $style_handles = array(
		'wicked-folders-admin',
	);

	protected $script_handles = array(
		'wicked-folders-admin',
		'wicked-folders-app',
	);
}

new GravityView_Plugin_Hooks_Wicked_Folders();
