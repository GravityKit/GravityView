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
 * @since 1.15.2
 */
class GravityView_Theme_Hooks_Genesis extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $function_name = 'genesis';

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $script_handles = array(
		'genesis_admin_js',
	);

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $style_handles = array(
		'genesis_admin_css',
	);

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $post_type_support = array(
		'genesis-layouts',
		'genesis-seo',
	);
}

new GravityView_Theme_Hooks_Genesis;