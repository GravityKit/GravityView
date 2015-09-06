<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://gravityview.co
 * @copyright Copyright 2013, Katz Web Services, Inc.
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}


class GravityView_Uninstall {

	/**
	 * GravityView_Uninstall constructor.
	 */
	public function __construct() {

		include_once plugin_dir_path( __FILE__ ) . 'includes/class-settings.php';

		include_once plugin_dir_path( __FILE__ ) . 'includes/class-gravityview-roles.php';

		$delete = GravityView_Settings::get_instance()->get_app_setting('delete-on-uninstall');

		if( empty( $delete ) ) {
			var_dump('DO NOT DELETE');
		} else {
			var_dump('DELETE!');
		}

		die(); // Take out later, of course

		$this->init();
	}

	private function init() {
		$this->delete_options();
		$this->delete_posts();
		$this->delete_capabilities();
		$this->delete_roles();
		$this->delete_capabilities();
		$this->delete_entry_meta();
	}

	/**
	 * Delete all GravityView-generated entry meta
	 * @todo
	 */
	private function delete_entry_meta() {
	}

	private function delete_roles() {
		GravityView_Roles::get_instance()->remove_roles();
	}

	private function delete_capabilities() {
		GravityView_Roles::get_instance()->remove_caps();
	}

	/**
	 * Delete all the GravityView custom post type objects
	 */
	private function delete_posts() {
		$items = get_posts( array(
			'post_type' => 'gravityview',
			'post_status' => 'any',
			'numberposts' => -1,
			'fields' => 'ids'
		) );

		if ( $items ) {
			foreach ( $items as $item ) {
				wp_delete_post( $item, true );
			}
		}
	}

	private function delete_options() {
		delete_option( 'gravityformsaddon_gravityview_version' );
		delete_option( 'gravityview_cache_blacklist' );
		delete_option( 'gravityformsaddon_gravityview_app_settings' );
	}
}

new GravityView_Uninstall;