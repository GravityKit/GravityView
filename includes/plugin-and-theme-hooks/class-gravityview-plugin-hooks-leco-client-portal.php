<?php
/**
 * Add support for embedding Views inside Client Portal by Laura Elizabeth modules
 *
 * @file      class-gravityview-plugin-hooks-leco-client-portal.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2018, Katz Web Services, Inc.
 *
 * @since 2.1
 */

/**
 * @inheritDoc
 * @since 2.1
 */
class GravityView_Plugin_Hooks_Leco_Client_Portal extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 2.1
	 */
	protected $class_name = 'LECO_Client_Portal';

	/**
	 * Define the keys to be parsed by the `gravityview/view_collection/from_post/meta_keys` hook
	 *
	 * @see View_Collection::from_post
	 * @since 2.0
	 * @type array
	 */
	protected $content_meta_keys = array(
		'leco_cp_cta',
		'leco_cp_part_0_module',
		'leco_cp_part_1_module',
		'leco_cp_part_2_module',
		'leco_cp_part_3_module',
		'leco_cp_part_4_module',
		'leco_cp_part_5_module',
		'leco_cp_part_6_module',
		'leco_cp_part_7_module',
		'leco_cp_part_8_module',
		'leco_cp_part_9_module',
		'leco_cp_part_10_module',
	);
}

new GravityView_Plugin_Hooks_Leco_Client_Portal();
