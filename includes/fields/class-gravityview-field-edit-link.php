<?php
/**
 * @file class-gravityview-field-edit-link.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Add custom options for edit_link fields
 */
class GravityView_Field_Edit_Link extends GravityView_Field {

	public $name = 'edit_link';

	public $contexts = array( 'single', 'multiple' );

	/**
	 * @var bool
	 */
	public $is_sortable = false;

	/**
	 * @var bool
	 */
	public $is_searchable = false;

	public $group = 'featured';

	public $icon = 'dashicons-welcome-write-blog';

	public function __construct() {
		$this->label       = esc_html__( 'Link to Edit Entry', 'gk-gravityview' );
		$this->description = esc_html__( 'A link to edit the entry. Visible based on View settings.', 'gk-gravityview' );

		parent::__construct();
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		if ( 'edit' === $context ) {
			return $field_options;
		}

		// Always a link, never a filter
		unset( $field_options['show_as_link'], $field_options['search_filter'] );

		// Edit Entry link should only appear to visitors capable of editing entries
		unset( $field_options['only_loggedin'], $field_options['only_loggedin_cap'] );

		$add_option['edit_link'] = array(
			'type'       => 'text',
			'label'      => __( 'Edit Link Text', 'gk-gravityview' ),
			'desc'       => null,
			'value'      => __( 'Edit Entry', 'gk-gravityview' ),
			'merge_tags' => true,
		);

		$this->add_field_support( 'lightbox', $field_options );
		$this->add_field_support( 'new_window', $field_options );

		return array_merge( $add_option, $field_options );
	}
}

new GravityView_Field_Edit_Link();
