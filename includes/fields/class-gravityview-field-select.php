<?php
/**
 * @file class-gravityview-field-select.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Select extends GravityView_Field {

	var $name = 'select';

	var $is_searchable = true;

	/**
	 * @see GFCommon::get_field_filter_settings
	 * @var array
	 */
	var $search_operators = array( 'is', 'isnot', 'contains' );

	var $_gf_field_class_name = 'GF_Field_Select';

	var $group = 'standard';

	public function __construct() {
		$this->label = esc_html__( 'Select', 'gravityview' );
		parent::__construct();
	}

	/**
	 * Add `choice_display` setting to the field
	 *
	 * @param array $field_options
	 * @param string $template_id
	 * @param string $field_id
	 * @param string $context
	 * @param string $input_type
	 *
	 * @since 1.17
	 *
	 * @return array
	 */
	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		// Set the $_field_id var
		$field_options = parent::field_options( $field_options, $template_id, $field_id, $context, $input_type );

		if( $this->is_choice_value_enabled() ) {
			$field_options['choice_display'] = array(
				'type'    => 'radio',
				'value'   => 'value',
				'label'   => __( 'What should be displayed:', 'gravityview' ),
				'desc'    => __( 'This input has a label and a value. What should be displayed?', 'gravityview' ),
				'choices' => array(
					'value' => __( 'Value of the input', 'gravityview' ),
					'label' => __( 'Label of the input', 'gravityview' ),
				),
			);
		}
		
		return $field_options;
	}

}

new GravityView_Field_Select;
