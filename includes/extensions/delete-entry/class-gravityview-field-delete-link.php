<?php
/**
 * @file class-gravityview-field-delete-link.php
 * @package GravityView
 * @subpackage includes\extensions\delete-entry\fields
 */

/**
 * Add custom options for delete_link fields
 */
class GravityView_Field_Delete_Link extends GravityView_Field {

	/** @inheritDoc  */
	var $name = 'delete_link';

	/** @inheritDoc  */
	var $contexts = array( 'multiple', 'single' );

	/** @inheritDoc  */
	var $is_sortable = false;

	/** @inheritDoc  */
	var $is_searchable = false;

	/** @inheritDoc  */
	var $group = 'featured';

	/** @inheritDoc  */
	var $icon = 'dashicons-trash';

	public function __construct() {
		$this->label       = esc_html__( 'Delete Entry', 'gk-gravityview' );
		$this->description = esc_html__( 'A link to delete the entry. Respects the Delete Entry permissions.', 'gk-gravityview' );

		$this->add_hooks();

		parent::__construct();
	}

	/**
	 * Add hooks for this field type.
	 *
	 * @return void
	 */
	public function add_hooks() {
		// For the Delete Entry Link, you don't want visible to all users.
		add_filter( 'gravityview_field_visibility_caps', array( $this, 'modify_visibility_caps' ), 10, 5 );
	}

	/**
	 * Change wording for the Edit context to read Entry Creator
	 *
	 * @since 1.5.1
	 * @since 2.9.2 Moved here from GravityView_Delete_Entry
	 *
	 * @param array  $visibility_caps Array of capabilities to display in field dropdown.
	 * @param string $field_type Type of field options to render (`field` or `widget`)
	 * @param string $template_id Table slug
	 * @param float  $field_id GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
	 * @param string $context What context are we in? Example: `single` or `directory`
	 * @param string $input_type (textarea, list, select, etc.)
	 *
	 * @return array                   Array of field options with `label`, `value`, `type`, `default` keys
	 */
	public function modify_visibility_caps( $visibility_caps = array(), $template_id = '', $field_id = '', $context = '', $input_type = '' ) {

		if ( $field_id !== $this->name ) {
			return $visibility_caps;
		}

		$caps = $visibility_caps;

		// If we're configuring fields in the edit context, we want a limited selection. Remove other built-in caps.
		unset( $caps['publish_posts'], $caps['gravityforms_view_entries'], $caps['delete_others_posts'] );

		$caps['read'] = _x( 'Entry Creator', 'User capability', 'gk-gravityview' );

		return $caps;
	}

	/**
	 * Add "Delete Link Text" setting to the edit_link field settings
	 *
	 * @since 1.5.1
	 * @since 2.9.2 Moved here from GravityView_Delete_Entry
	 * @since TODO Moved to own field class.
	 *
	 * @param array  $field_options
	 * @param string $template_id
	 * @param string $field_id
	 * @param string $context
	 * @param string $input_type
	 *
	 * @return array $field_options, with "Delete Link Text" and "Allow the following users to delete the entry:" field options.
	 */
	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

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
			'type'     => 'select',
			'label'    => __( 'Allow the following users to delete the entry:', 'gk-gravityview' ),
			'choices'  => GravityView_Render_Settings::get_cap_choices( $template_id, $field_id, $context, $input_type ),
			'tooltip'  => 'allow_edit_cap',
			'class'    => 'widefat',
			'value'    => 'read', // Default: entry creator
			'group'    => 'visibility',
			'priority' => 100,
		);

		return array_merge( $add_option, $field_options );
	}
}

new GravityView_Field_Delete_Link();
