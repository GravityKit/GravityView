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
 */
class GravityView_Plugin_Hooks_Debug_Bar extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 */
	protected $class_name = 'Debug_Bar';

	/**
	 * @inheritDoc
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