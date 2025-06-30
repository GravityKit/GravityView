<?php
/**
 * Add WooCommerce scripts and styles to GravityView no-conflict list
 *
 * @file      class-gravityview-plugin-hooks-woocommerce.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15.2
 */

/**
 * @inheritDoc
 */
class GravityView_Plugin_Hooks_WooCommerce extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * The function name to fetch WooCommerce page IDs.
	 *
	 * @since TODO
	 *
	 * @var string
	 */
	protected $function_name = 'wc_get_page_id';

	/**
	 * @inheritDoc
	 */
	protected $style_handles = array(
		'woocommerce_admin_menu_styles',
		'woocommerce_admin_styles',
	);

	/**
	 * Remove the permalink structure for LearnDash post types.
	 *
	 * @since TODO
	 *
	 * @return bool Whether to remove the permalink structure from View rendered links.
	 */
	public function should_disable_permalink_structure() {

		$page_id = wc_get_page_id( 'myaccount' );

		if ( get_the_ID() !== $page_id ) {
			return parent::should_disable_permalink_structure();
		}

		return true;
	}
}

new GravityView_Plugin_Hooks_WooCommerce();
