<?php
/**
 * Add GeneratePress Theme compatibility to GravityView
 *
 * @file      class-gravityview-theme-hooks-generatepress.php
 * @since     1.15.2
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @package   GravityView
 */

/**
 * @inheritDoc
 * @since 1.15.2
 */
class GravityView_Theme_Hooks_GeneratePress extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $constant_name = 'GENERATE_VERSION';

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $content_meta_keys = array(
		'_generate-sidebar-layout-meta',
		'_generate-footer-widget-meta',
	);
}

new GravityView_Theme_Hooks_GeneratePress();
