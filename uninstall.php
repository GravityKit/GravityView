<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete GravityView content when GravityView is uninstalled, if the setting is set to "Delete on Uninstall"
 * @since 1.14
 */
class GravityView_Uninstall {

	public function __construct() {

		/** @define "$file_path" "./" */
		$file_path = plugin_dir_path( __FILE__ );

		include_once  $file_path . 'includes/class-settings.php';
		include_once $file_path . 'includes/class-gravityview-roles.php';

		/**
		 * Only delete content and settings if "Delete on Uninstall?" setting is "Permanently Delete"
		 * @var string|null $delete NULL if not configured (previous versions); "0" if false, "delete" if delete
		 */
		$delete = GravityView_Settings::get_instance()->get_app_setting('delete-on-uninstall');

		if( 'delete' === $delete ) {
			$this->fire_everything();
		}
	}

	/**
	 * Delete GravityView Views, settings, roles, caps, etc.
	 * @see https://youtu.be/FXy_DO6IZOA?t=35s
	 * @since 1.14
	 */
	private function fire_everything() {
		$this->delete_options();
		$this->delete_posts();
		$this->delete_roles();
		$this->delete_capabilities();
		$this->delete_entry_meta();
		$this->delete_entry_notes();
	}

	/**
	 * Delete GravityView "approved entry" meta
	 * @since 1.14
	 */
	private function delete_entry_meta() {
		global $wpdb;

		$meta_table = class_exists( 'GFFormsModel' ) ? GFFormsModel::get_lead_meta_table_name() : $wpdb->prefix . 'rg_lead_meta';

		$sql = "
			DELETE FROM $meta_table
			WHERE (
				`meta_key` = 'is_approved'
			);
		";

		$wpdb->query( $sql );
	}

	/**
	 * Delete all GravityView-generated entry notes
	 * @since 1.14
	 */
	private function delete_entry_notes() {
		global $wpdb;

		$notes_table = class_exists( 'GFFormsModel' ) ? GFFormsModel::get_lead_notes_table_name() : $wpdb->prefix . 'rg_lead_notes';

		$disapproved = __('Disapproved the Entry for GravityView', 'gravityview');
		$approved = __('Approved the Entry for GravityView', 'gravityview');

		$sql = $wpdb->prepare( "
			DELETE FROM $notes_table
            WHERE (
                `note_type` = 'gravityview' OR
				`value` = %s OR
				`value` = %s
            );
        ", $approved, $disapproved );

		$wpdb->query( $sql );
	}

	/**
	 * Delete capabilities added by GravityView
	 * @since 1.14
	 */
	private function delete_capabilities() {
		GravityView_Roles_Capabilities::get_instance()->remove_caps();
	}

	/**
	 * Delete all the GravityView custom post type posts
	 * @since 1.14
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

	/**
	 * Delete GravityView options
	 * @since 1.14
	 */
	private function delete_options() {

		delete_option( 'gravityformsaddon_gravityview_app_settings' );
		delete_option( 'gravityformsaddon_gravityview_version' );
		delete_option( 'gravityview_cache_blacklist' );

		delete_transient( 'gravityview_edd-activate_valid' );
		delete_transient( 'gravityview_edd-deactivate_valid' );
		delete_transient( 'gravityview_dismissed_notices' );
	}
}

new GravityView_Uninstall;