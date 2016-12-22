<?php
/**
 * Add WPML compatibility to GravityView, including registering scripts and styles to GravityView no-conflict list
 *
 * @file      class-gravityview-theme-hooks-wpml.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.19.2
 */

/**
 * @inheritDoc
 */
class GravityView_Theme_Hooks_WPML extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.19.2
	 */
	protected $script_handles = array(
		'wpml-cpi-scripts',
		'sitepress-scripts',
		'sitepress-post-edit',
		'sitepress-post-list-quickedit',
		'sitepress-languages',
		'sitepress-troubleshooting',
	);

	/**
	 * @inheritDoc
	 * @since 1.19.2
	 */
	protected $style_handles = array(
		'wpml-select-2',
		'wpml-tm-styles',
		'wpml-tm-queue',
		'wpml-dialog',
		'wpml-tm-editor-css',
	);

	/**
	 * @inheritDoc
	 * @since 1.19.2
	 */
	protected $constant_name = 'ICL_SITEPRESS_VERSION';

}

new GravityView_Theme_Hooks_WPML;