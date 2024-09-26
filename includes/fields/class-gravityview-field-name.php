<?php
/**
 * @file class-gravityview-field-name.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Name extends GravityView_Field {

	var $name = 'name';

	/** @see GF_Field_Name */
	var $_gf_field_class_name = 'GF_Field_Name';

	var $group = 'advanced';

	public $search_operators = array( 'is', 'isnot', 'contains' );

	var $is_searchable = true;

	var $icon = 'dashicons-admin-users';

	public function __construct() {
		$this->label = esc_html__( 'Name', 'gk-gravityview' );

		parent::__construct();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 */
	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {
		if ( 'edit' === $context ) {
			return $field_options;
		}

		$field_options['show_as_initials'] = array(
			'type'     => 'checkbox',
			'label'    => __( 'Show as initials', 'gk-gravityview' ),
			'desc'     => __( 'This displays the first letter of the first and last names.', 'gk-gravityview' ),
			'value'    => '',
			'group'    => 'display',
		);


		return $field_options;
	}


}

new GravityView_Field_Name();
