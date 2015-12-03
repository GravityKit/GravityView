<?php
/**
 * Add WooThemes Framework compatibility to GravityView, including registering scripts and styles to GravityView no-conflict list
 *
 * @file      class-gravityview-theme-hooks-woothemes.php
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
class GravityView_Theme_Hooks_WooThemes extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $script_handles = array(
		'woo-shortcodes',
		'woo-custom-fields',
		'woo-medialibrary-uploader',
		'jquery-masked-input',
		'woo-upload',
		'woo-datepicker',
		'woo-colourpicker',
		'woo-typography',
		'woo-masked-input',
		'woo-chosen',
		'woo-chosen-rtl',
		'woo-chosen-loader',
		'woo-image-selector',
		'woo-range-selector',
	);

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $style_handles = array(
		'woo-menu',
		'wf-admin',
		'woothemes-fields',
		'woo-fields',
		'woo-chosen',
		'woothemes-chosen',
	);

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $function_name = 'woo_version';

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	function add_hooks() {

		parent::add_hooks();

		add_action( 'admin_menu', array( $this, 'remove_meta_box' ), 11 );
	}

	/**
	 * Remove the WooThemes metabox on new page
	 * @since 1.15.2
	 */
	function remove_meta_box() {
		global $pagenow;

		$gv_page = gravityview_is_admin_page( '', 'single' );

		// New View or Edit View page
		if( $gv_page && $pagenow === 'post-new.php' ) {
			remove_meta_box( 'woothemes-settings', 'gravityview', 'normal' );
		}
	}

}

new GravityView_Theme_Hooks_WooThemes;