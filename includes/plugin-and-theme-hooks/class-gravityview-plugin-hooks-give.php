<?php
/**
 * Customization for GiveWP.
 *
 * @file      class-gravityview-plugin-hooks-give.php
 * @package   GravityView
 * @license   GPL2
 * @author    GravityKit <hello@gravitykit.com>
 * @link      https://www.gravitykit.com
 * @copyright Copyright 2023, Katz Web Services, Inc.
 *
 * @since 2.17.2
 */

/**
 * @inheritDoc
 * @since 2.16
 */
class GravityView_Plugin_Hooks_Give extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @type string Optional. Constant that should be defined by plugin or theme. Used to check whether plugin is active.
	 * @since 2.17.2
	 */
	protected $constant_name = 'GIVE_VERSION';

	/**
	 * Prevent Give from displaying styles that conflict with GravityView.
	 * @return void
	 */
	protected function add_hooks() {
		if( gravityview()->request->is_admin() ) {
			add_filter( 'give_load_admin_styles', '__return_false' );
		}
	}
}

new GravityView_Plugin_Hooks_Give;
