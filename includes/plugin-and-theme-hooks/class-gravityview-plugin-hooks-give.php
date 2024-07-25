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
 */
class GravityView_Plugin_Hooks_Give extends GravityView_Plugin_and_Theme_Hooks {
	/**
	 * @type string Optional. Constant that should be defined by plugin or theme. Used to check whether plugin is active.
	 * @since 2.17.2
	 */
	protected $constant_name = 'GIVE_VERSION';

	public function __construct() {
		parent::__construct();

		add_action( 'add_meta_boxes', array( $this, 'block_styles' ) );
	}

	/**
	 * Prevent Give from loading styles that conflict with GravityView.
	 *
	 * @return void
	 */
	public function block_styles() {
		if ( gravityview()->request->is_admin() && gravityview()->request->is_view() ) {
			add_filter( 'give_load_admin_styles', '__return_false' );
		}
	}
}

new GravityView_Plugin_Hooks_Give();
