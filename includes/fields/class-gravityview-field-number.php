<?php
/**
 * @file class-gravityview-field-number.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 1.13
 */

/**
 * Add custom options for number fields
 *
 * @since 1.13
 */
class GravityView_Field_Number extends GravityView_Field {

	var $name = 'number';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot', 'greater_than', 'less_than' );

	/** @see GF_Field_Number */
	var $_gf_field_class_name = 'GF_Field_Number';

	var $group = 'standard';

	public function __construct() {
		$this->label = esc_html__( 'Number', 'gravityview' );
		parent::__construct();
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		$field_options['number_format'] = array(
			'type' => 'checkbox',
			'label' => __( 'Format number?', 'gravityview' ),
			'desc' => __('Display numbers with thousands separators.', 'gravityview'),
			'value' => false,
		);

		$field_options['decimals'] = array(
			'type' => 'number',
			'label' => __( 'Decimals', 'gravityview' ),
			'desc' => __('Precision of the number of decimal places. Leave blank to use existing precision.', 'gravityview'),
			'value' => '',
			'merge_tags' => false,
		);

		return $field_options;
	}

}

new GravityView_Field_Number;
