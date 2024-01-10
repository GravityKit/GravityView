<?php
/**
 * Add Query Monitor customizations
 *
 * @file      class-gravityview-plugin-hooks-query-monitor.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.16.5
 */

/**
 * @inheritDoc
 * @since 2.0
 */
class GravityView_Plugin_Hooks_Query_Monitor extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @since 2.0
	 */
	protected $class_name = 'QueryMonitor';

	/**
	 * @since 2.0
	 */
	protected $script_handles = array(
		'query-monitor',
	);

	/**
	 * @since 2.0
	 */
	protected $style_handles = array(
		'query-monitor',
	);
}

new GravityView_Plugin_Hooks_Query_Monitor();
