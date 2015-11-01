<?php
/**
 * Add Yoast SEO scripts and styles to GravityView no-conflict list
 *
 * @file      class-gravityview-plugin-hooks-yoast-seo.php
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
class GravityView_Plugin_Hooks_Yoast_SEO extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 */
	protected $constant_name = 'WPSEO_FILE';

	/**
	 * @inheritDoc
	 */
	protected $style_handles = array(
		'wp-seo-metabox',
		'wpseo-admin-media',
		'metabox-tabs',
		'metabox-classic',
		'metabox-fresh',
	);

	/**
	 * @inheritDoc
	 */
	protected $script_handles = array(
		'wp-seo-metabox',
		'wpseo-admin-media',
		'jquery-qtip',
		'jquery-ui-autocomplete',
	);
}

new GravityView_Plugin_Hooks_Yoast_SEO;