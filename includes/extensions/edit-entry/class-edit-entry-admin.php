<?php
/**
 * GravityView Edit Entry - Admin logic
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


class GravityView_Edit_Entry_Admin {

	protected $loader;

	function __construct( GravityView_Edit_Entry $loader ) {
		$this->loader = $loader;
	}

	function load() {

		if ( ! is_admin() ) {
			return;
		}

		// For the Edit Entry Link, you don't want visible to all users.
		add_filter( 'gravityview_field_visibility_caps', array( $this, 'modify_visibility_caps' ), 10, 5 );

		// add tooltips
		add_filter( 'gravityview/metaboxes/tooltips', array( $this, 'tooltips' ) );

		// custom fields' options for zone EDIT
		add_filter( 'gravityview_template_field_options', array( $this, 'field_options' ), 10, 6 );

		// Add Edit Entry settings to View Settings
		add_action( 'gravityview/metaboxes/edit_entry', array( $this, 'view_settings_metabox' ) );
	}

	/**
	 * Render Edit Entry View metabox settings
	 *
	 * @since 2.9
	 *
	 * @param $current_settings
	 *
	 * @return void
	 */
	public function view_settings_metabox( $current_settings ) {
		GravityView_Render_Settings::render_setting_row( 'edit_locking', $current_settings );

		GravityView_Render_Settings::render_setting_row( 'edit_locking_check_interval', $current_settings );

		GravityView_Render_Settings::render_setting_row( 'user_edit', $current_settings );

		GravityView_Render_Settings::render_setting_row( 'unapprove_edit', $current_settings );

		GravityView_Render_Settings::render_setting_row( 'edit_redirect', $current_settings );

		GravityView_Render_Settings::render_setting_row( 'edit_redirect_url', $current_settings );

		GravityView_Render_Settings::render_setting_row( 'action_label_update', $current_settings );

		GravityView_Render_Settings::render_setting_row( 'edit_cancel_lightbox_action', $current_settings );

		GravityView_Render_Settings::render_setting_row( 'action_label_next', $current_settings );

		GravityView_Render_Settings::render_setting_row( 'action_label_previous', $current_settings );

		GravityView_Render_Settings::render_setting_row( 'action_label_cancel', $current_settings );
	}

	/**
	 * Change wording for the Edit context to read Entry Creator
	 *
	 * @param  array  $visibility_caps        Array of capabilities to display in field dropdown.
	 * @param  string $field_type  Type of field options to render (`field` or `widget`)
	 * @param  string $template_id Table slug
	 * @param  float  $field_id    GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
	 * @param  string $context     What context are we in? Example: `single` or `directory`
	 * @param  string $input_type  (textarea, list, select, etc.)
	 * @return array                   Array of field options with `label`, `value`, `type`, `default` keys
	 */
	function modify_visibility_caps( $visibility_caps = array(), $template_id = '', $field_id = '', $context = '', $input_type = '' ) {

		if ( 'edit' !== $context ) {
			return $visibility_caps;
		}

		// If we're configuring fields in the edit context, we want a limited selection.
		$caps = $visibility_caps;

		// Remove other built-in caps.
		unset( $caps['publish_posts'], $caps['gravityforms_view_entries'], $caps['delete_others_posts'] );

		$caps['read'] = _x( 'Entry Creator', 'User capability', 'gk-gravityview' );

		return $caps;
	}

	/**
	 * Add tooltips
	 *
	 * @param  array $tooltips Existing tooltips
	 * @return array           Modified tooltips
	 */
	function tooltips( $tooltips ) {

		$return = $tooltips;

		$return['allow_edit_cap'] = array(
			'title' => __( 'Limiting Edit Access', 'gk-gravityview' ),
			'value' => __( 'Change this setting if you don\'t want the user who created the entry to be able to edit this field.', 'gk-gravityview' ),
		);

		return $return;
	}

	/**
	 * Add "Edit Link Text" setting to the edit_link field settings
	 *
	 * @param array  $field_options
	 * @param string $template_id
	 * @param string $field_id
	 * @param string $context
	 * @param string $input_type
	 * @param int    $form_id
	 *
	 * @return array $field_options, with added field options
	 */
	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		// We only want to modify the settings for the edit context
		if ( 'edit' !== $context ) {
			return $field_options;
		}

		// Entry field is only for logged in users
		unset( $field_options['only_loggedin'], $field_options['only_loggedin_cap'] );

		$add_options = array(
			'allow_edit_cap' => array(
				'type'    => 'select',
				'label'   => __( 'Make field editable to:', 'gk-gravityview' ),
				'choices' => GravityView_Render_Settings::get_cap_choices( $template_id, $field_id, $context, $input_type ),
				'tooltip' => 'allow_edit_cap',
				'class'   => 'widefat',
				'value'   => 'read', // Default: entry creator
				'group'   => 'visibility',
			),
		);

		return array_merge( $field_options, $add_options );
	}
} // end class
