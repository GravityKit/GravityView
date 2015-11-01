<?php
/**
 * Add Genesis Framework compatibility to GravityView, including registering scripts and styles to GravityView no-conflict list
 *
 * @file      class-gravityview-theme-hooks-genesis.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15.2
 */

/**
 * @inheritDoc
 */
class GravityView_Theme_Hooks_Genesis extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 */
	protected $function_name = 'genesis';

	/**
	 * @inheritDoc
	 */
	protected $script_handles = array(
		'genesis_admin_js',
	);

	/**
	 * @inheritDoc
	 */
	protected $style_handles = array(
		'genesis_admin_css',
	);
}

new GravityView_Theme_Hooks_Genesis;