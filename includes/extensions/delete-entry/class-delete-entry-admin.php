<?php
/**
 * GravityView Delete Entry - Admin logic
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityView <hello@gravityview.co>
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}


class GravityView_Delete_Entry_Admin {

	protected $loader;

	public function __construct( GravityView_Delete_Entry $loader ) {
		$this->loader = $loader;
	}

	public function load() {

		if ( ! is_admin() ) {
			return;
		}

		// Add Delete Entry settings to View Settings Metabox.
		add_action( 'gravityview/metaboxes/delete_entry', array( $this, 'view_settings_metabox' ) );
	}

	/**
	 * Render Delete Entry View metabox settings
	 *
	 * @since 2.9.1
	 *
	 * @param $current_settings
	 *
	 * @return void
	 */
	public function view_settings_metabox( $current_settings ) {

		GravityView_Render_Settings::render_setting_row( 'delete_redirect', $current_settings );

		GravityView_Render_Settings::render_setting_row( 'delete_redirect_url', $current_settings );
	}
}
