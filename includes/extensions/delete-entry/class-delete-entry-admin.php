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

		// Add Delete Entry settings to Delete Entry Settings Metabox.
		add_action( 'gravityview/metaboxes/delete_entry', array( $this, 'view_settings_metabox' ) );

		// Add Delete Entry settings to Edit Entry Settings Metabox.
		add_action( 'gravityview/metaboxes/edit_entry', array( $this, 'view_settings_edit_entry_metabox' ), 20 );

		// For the Delete Entry Link, you don't want visible to all users.
		add_filter( 'gravityview_field_visibility_caps', array( $this, 'modify_visibility_caps' ), 10, 5 );

		// Modify the field options based on the name of the field type
		add_filter( 'gravityview_template_delete_link_options', array( $this, 'delete_link_field_options' ), 10, 5 );

		// Add Delete Entry settings to View Settings
		add_action( 'gravityview/metaboxes/delete_entry', array( $this, 'view_settings_delete_entry_metabox' ), 7 );

		// Add Delete Link as a default field, outside those set in the Gravity Form form
		add_filter( 'gravityview_entry_default_fields', array( $this, 'add_default_field' ), 10, 3 );
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
	 * Change wording for the Edit context to read Entry Creator
	 *
	 * @since 1.5.1
	 * @since 2.9.2 Moved here from GravityView_Delete_Entry
	 *
	 * @param array $visibility_caps Array of capabilities to display in field dropdown.
	 * @param string $field_type Type of field options to render (`field` or `widget`)
	 * @param string $template_id Table slug
	 * @param float $field_id GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
	 * @param string $context What context are we in? Example: `single` or `directory`
	 * @param string $input_type (textarea, list, select, etc.)
	 *
	 * @return array                   Array of field options with `label`, `value`, `type`, `default` keys
	 */
	public function modify_visibility_caps( $visibility_caps = array(), $template_id = '', $field_id = '', $context = '', $input_type = '' ) {

		$caps = $visibility_caps;

		// If we're configuring fields in the edit context, we want a limited selection
		if ( $field_id === 'delete_link' ) {

			// Remove other built-in caps.
			unset( $caps['publish_posts'], $caps['gravityforms_view_entries'], $caps['delete_others_posts'] );

			$caps['read'] = _x( 'Entry Creator', 'User capability', 'gk-gravityview' );
		}

		return $caps;
	}

	/**
	 * Add "Delete Link Text" setting to the edit_link field settings
	 *
	 * @since 1.5.1
	 * @since 2.9.2 Moved here from GravityView_Delete_Entry
	 *
	 * @param array $field_options
	 * @param string $template_id
	 * @param string $field_id
	 * @param string $context
	 * @param string $input_type
	 *
	 * @return array $field_options, with "Delete Link Text" and "Allow the following users to delete the entry:" field options.
	 */
	public function delete_link_field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		// Always a link, never a filter
		unset( $field_options['show_as_link'], $field_options['search_filter'] );

		// Delete Entry link should only appear to visitors capable of editing entries
		unset( $field_options['only_loggedin'], $field_options['only_loggedin_cap'] );

		$add_option['delete_link'] = array(
			'type'       => 'text',
			'label'      => __( 'Delete Link Text', 'gk-gravityview' ),
			'desc'       => null,
			'value'      => __( 'Delete Entry', 'gk-gravityview' ),
			'merge_tags' => true,
		);

		$field_options['allow_edit_cap'] = array(
			'type'    => 'select',
			'label'   => __( 'Allow the following users to delete the entry:', 'gk-gravityview' ),
			'choices' => GravityView_Render_Settings::get_cap_choices( $template_id, $field_id, $context, $input_type ),
			'tooltip' => 'allow_edit_cap',
			'class'   => 'widefat',
			'value'   => 'read', // Default: entry creator
			'group'   => 'visibility',
			'priority' => 100,
		);

		return array_merge( $add_option, $field_options );
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

	/**
	 * Add Edit Link as a default field, outside those set in the Gravity Form form
	 *
	 * @since 1.5.1
	 * @since 2.9.2 Moved here from GravityView_Delete_Entry
	 *
	 * @param array $entry_default_fields Existing fields
	 * @param string|array $form form_ID or form object
	 * @param string $zone Either 'single', 'directory', 'edit', 'header', 'footer'
	 *
	 * @return array
	 */
	public function add_default_field( $entry_default_fields, $form = array(), $zone = '' ) {

		if ( 'edit' !== $zone ) {
			$entry_default_fields['delete_link'] = array(
				'label' => __( 'Delete Entry', 'gk-gravityview' ),
				'type'  => 'delete_link',
				'desc'  => __( 'A link to delete the entry. Respects the Delete Entry permissions.', 'gk-gravityview' ),
				'icon'  => 'dashicons-trash',
			);
		}

		return $entry_default_fields;
	}
}
