<?php
/**
 * Add Avia Framework theme compatibility to GravityView
 *
 * @file      class-gravityview-theme-hooks-avia.php
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
class GravityView_Theme_Hooks_Avia extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $constant_name = 'AV_FRAMEWORK_VERSION';

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $meta_keys = array(
		'_aviaLayoutBuilderCleanData'
	);

}

new GravityView_Theme_Hooks_Avia;