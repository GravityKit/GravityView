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

/**
 * Delete GravityView content when GravityView is uninstalled, if the setting is set to "Delete on Uninstall"
 * @since 1.15
 */
class GravityView_Uninstall {

	/**
	 * Delete GravityView Views, settings, roles, caps, etc.
	 * @see https://youtu.be/FXy_DO6IZOA?t=35s
	 * @since 1.15
	 * @return void
	 */
	public function fire_everything() {
		$this->delete_posts();
		$this->delete_capabilities();
		$this->delete_entry_meta();
		$this->delete_entry_notes();

		// Keep this as last to make sure the GravityView Cache blacklist option is deleted
		$this->delete_options();
	}

	/**
	 * Delete GravityView "approved entry" meta
	 * @since 1.15
	 * @return void
	 */
	private function delete_entry_meta() {
		global $wpdb;

		$tables = array();

		if ( method_exists( 'GFFormsModel', 'get_entry_meta_table_name' ) ) {
			$tables []= GFFormsModel::get_entry_meta_table_name();
		} else if ( method_exists( 'GFFormsModel', 'get_lead_meta_table_name' ) ) {
			$tables []= GFFormsModel::get_lead_meta_table_name();
		} else {
			$tables []= $wpdb->prefix . 'rg_lead_meta';
			$tables []= $wpdb->prefix . 'gf_entry_meta';
		}

		$suppress = $wpdb->suppress_errors();
		foreach ( $tables as $meta_table ) {
			$sql = "
				DELETE FROM $meta_table
				WHERE (
					`meta_key` = 'is_approved'
				);
			";
			$wpdb->query( $sql );
		}
		$wpdb->suppress_errors( $suppress );
	}

	/**
	 * Delete all GravityView-generated entry notes
	 * @since 1.15
	 * @return void
	 */
	private function delete_entry_notes() {
		global $wpdb;

		$tables = array();

		if ( method_exists( 'GFFormsModel', 'get_entry_notes_table_name' ) ) {
			$tables []= GFFormsModel::get_entry_notes_table_name();
		} else if ( method_exists( 'GFFormsModel', 'get_lead_notes_table_name' ) ) {
			$tables []= GFFormsModel::get_lead_notes_table_name();
		} else {
			$tables []= $wpdb->prefix . 'rg_lead_notes';
			$tables []= $wpdb->prefix . 'gf_entry_notes';
		}

		$disapproved = __('Disapproved the Entry for GravityView', 'gravityview');
		$approved = __('Approved the Entry for GravityView', 'gravityview');

		$suppress = $wpdb->suppress_errors();
		foreach ( $tables as $notes_table ) {
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
		$wpdb->suppress_errors( $suppress );
	}

	/**
	 * Delete capabilities added by GravityView
	 * @since 1.15
	 * @return void
	 */
	private function delete_capabilities() {
		GravityView_Roles_Capabilities::get_instance()->remove_caps();
	}

	/**
	 * Delete all the GravityView custom post type posts
	 * @since 1.15
	 * @return void
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
	 * @since 1.15
	 * @return void
	 */
	private function delete_options() {
		delete_option( 'gravityview_cache_blacklist' );
		delete_option( 'gv_version_upgraded_from' );
		delete_transient( 'gravityview_edd-activate_valid' );
		delete_transient( 'gravityview_edd-deactivate_valid' );
		delete_transient( 'gravityview_dismissed_notices' );
		delete_site_transient( 'gravityview_related_plugins' );
	}
}