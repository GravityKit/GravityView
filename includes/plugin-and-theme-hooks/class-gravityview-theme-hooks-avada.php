<?php
/**
 * Add Avada Theme compatibility to GravityView, including registering scripts and styles to GravityView no-conflict list
 *
 * @file      class-gravityview-theme-hooks-avada.php
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
class GravityView_Theme_Hooks_Avada extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $function_name = 'avada_scripts';

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $content_meta_keys = array(
		'sbg_selected_sidebar',
	);

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $script_handles = array(
		'jquery.biscuit',
		'avada_upload',
		'tipsy',
		'jquery-ui-slider',
		'smof',
		'cookie',
		'kd-multiple-featured-images',
	);
}

new GravityView_Theme_Hooks_Avada;