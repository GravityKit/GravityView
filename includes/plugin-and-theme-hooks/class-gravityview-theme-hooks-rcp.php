<?php
/**
 * Register RCP scripts and styles to GravityView no-conflict list
 *
 * @file      class-gravityview-theme-hooks-rcp.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      https://gravityview.co
 * @copyright Copyright 2017, Katz Web Services, Inc.
 *
 * @since 1.21.5
 */

/**
 * @inheritDoc
 */
class GravityView_Theme_Hooks_RCP extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.21.5
	 */
	protected $script_handles = array(
		'rcp-admin-scripts',
	    'bbq',
	);

	/**
	 * @inheritDoc
	 * @since 1.21.5
	 */
	protected $constant_name = 'RCP_PLUGIN_VERSION';

}

new GravityView_Theme_Hooks_RCP;