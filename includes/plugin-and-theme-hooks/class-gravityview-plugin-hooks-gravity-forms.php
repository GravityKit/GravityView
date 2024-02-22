<?php
/**
 * Add Gravity Forms scripts and styles to GravityView no-conflict list
 *
 * @file      class-gravityview-plugin-hooks-gravity-forms.php
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
 * @since 1.15.2
 */
class GravityView_Plugin_Hooks_Gravity_Forms extends GravityView_Plugin_and_Theme_Hooks {

	public $class_name = 'GFForms';

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $style_handles = array(
		'gform_tooltip',
		'gform_font_awesome',
		'gform_admin_icons',
	);

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $script_handles = array(
		'gform_tooltip_init',
		'gform_field_filter',
		'gform_forms',
	);
}

new GravityView_Plugin_Hooks_Gravity_Forms();
