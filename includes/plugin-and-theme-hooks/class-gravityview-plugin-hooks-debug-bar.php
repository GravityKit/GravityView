<?php
/**
 * Add Debug Bar scripts and styles to GravityView no-conflict list
 *
 * @file      class-gravityview-plugin-hooks-gravity-forms.php
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
class GravityView_Plugin_Hooks_Debug_Bar extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $class_name = 'Debug_Bar';

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $style_handles = array(
		'debug-bar-extender',
		'debug-bar',
		'debug-bar-codemirror',
		'debug-bar-console',
		'puc-debug-bar-style',
	);

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $script_handles = array(
		'debug-bar-extender',
		'debug-bar',
		'debug-bar-codemirror',
		'debug-bar-console',
		'puc-debug-bar-js',
	);
}

new GravityView_Plugin_Hooks_Debug_Bar;