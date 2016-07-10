<?php
/**
 * Add Elegant Themes compatibility to GravityView (Divi theme)
 *
 * @file      class-gravityview-theme-hooks-elegant-themes.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2016', Katz Web Services, Inc.
 *
 * @since 1.17.2
 */

/**
 * @inheritDoc
 * @since 1.17.2
 */
class GravityView_Theme_Hooks_Elegant_Themes extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.17.2
	 */
	protected $function_name = 'et_setup_theme';

	function add_hooks() {
		parent::add_hooks();

		add_action( 'admin_init', array( $this, 'add_hooks_admin_init' ), 1 );
	}

	/**
	 * Prevent Divi from adding their stuff to GV pages
	 */
	public function add_hooks_admin_init() {
		if( GravityView_Admin::is_admin_page() ) {
			// Prevent Divi from adding import/export modal dialog
			remove_action( 'admin_init', 'et_pb_register_builder_portabilities' );

			// Divi theme adds their quicktag shortcode buttons on a View CPT. This causes JS errors.
			remove_action( 'admin_head', 'et_add_simple_buttons' );
		}
	}
}

new GravityView_Theme_Hooks_Elegant_Themes;