<?php
/**
 * Add WooCommerce scripts and styles to GravityView no-conflict list
 *
 * @file      class-gravityview-plugin-hooks-woocommerce.php
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
class GravityView_Plugin_Hooks_WooCommerce extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 */
	protected $style_handles = array(
		'woocommerce_admin_menu_styles',
		'woocommerce_admin_styles',
	);

}

new GravityView_Plugin_Hooks_WooCommerce;