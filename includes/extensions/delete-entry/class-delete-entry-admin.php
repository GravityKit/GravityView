<?php
/**
 * GravityView Delete Entry - Admin logic
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
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

		// Add Delete Entry settings to Delete Entry Settings Metabox.
		add_action( 'gravityview/metaboxes/delete_entry', array( $this, 'view_settings_metabox' ) );

		// Add Delete Entry settings to Edit Entry Settings Metabox.
		add_action( 'gravityview/metaboxes/edit_entry', array( $this, 'view_settings_edit_entry_metabox' ), 20 );

		// Add Delete Entry settings to View Settings
		add_action( 'gravityview/metaboxes/delete_entry', array( $this, 'view_settings_delete_entry_metabox' ), 7 );
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

	/**
	 * Renders settings relating to Delete Entry that should appear in the Edit Entry metabox
	 *
	 * @since 2.11
	 *
	 * @param $current_settings
	 */
	public function view_settings_edit_entry_metabox( $current_settings ) {
		GravityView_Render_Settings::render_setting_row( 'action_label_delete', $current_settings );
	}


	/**
	 * Add Delete Entry Link to the Add Field dialog
	 *
	 * @since 1.5.1
	 * @since 2.9.2 Moved here from GravityView_Delete_Entry
	 *
	 * @param array $available_fields
	 *
	 * @return array
	 */
	public function add_available_field( $available_fields = array() ) {

		$available_fields['delete_link'] = array(
			'label_text'    => __( 'Delete Entry', 'gk-gravityview' ),
			'field_id'      => 'delete_link',
			'label_type'    => 'field',
			'input_type'    => 'delete_link',
			'field_options' => null,
			'icon'          => 'dashicons-trash',
			'group'         => 'gravityview',
		);

		return $available_fields;
	}

	/**
	 * Render Delete Entry Permissions settings
	 *
	 * @since 2.9.2
	 *
	 * @param $current_settings
	 *
	 * @return void
	 */
	public function view_settings_delete_entry_metabox( $current_settings ) {

		GravityView_Render_Settings::render_setting_row( 'user_delete', $current_settings );
	}
}
