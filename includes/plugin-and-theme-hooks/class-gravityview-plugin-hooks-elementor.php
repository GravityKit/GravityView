<?php
/**
 * Add Elegant Themes compatibility to GravityView (Divi theme)
 *
 * @file      class-gravityview-theme-hooks-elegant-themes.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2016', Katz Web Services, Inc.
 *
 * @since 1.17.2
 */

/**
 * @inheritDoc
 * @since 1.17.2
 */
class GravityView_Theme_Hooks_Elementor extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.17.2
	 */
	protected $constant_name = 'ELEMENTOR_VERSION';

	protected $content_meta_keys = array( '_elementor_data' );
}

new GravityView_Theme_Hooks_Elementor();
