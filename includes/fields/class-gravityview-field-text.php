<?php
/**
 * @file class-gravityview-field-text.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Text extends GravityView_Field {

	var $name = 'text';

	var $_gf_field_class_name = 'GF_Field_Text';

	var $is_searchable = true;

	var $search_operators = array( 'contains', 'is', 'isnot', 'starts_with', 'ends_with' );

	var $group = 'standard';

	public function __construct() {
		$this->label = esc_html__( 'Single Line Text', 'gravityview' );
		parent::__construct();
	}

	/**
	 * @inheritDoc
	 * @since 2.9.1 Added "This content is numeric" setting
	 */
	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		$this->add_field_support( 'is_numeric', $field_options );

		return $field_options;
	}
}

new GravityView_Field_Text;
