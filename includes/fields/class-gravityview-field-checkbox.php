<?php
/**
 * @file class-gravityview-field-checkbox.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Checkbox extends GravityView_Field {

	var $name = 'checkbox';

	var $is_searchable = true;

	/**
	 * @see GFCommon::get_field_filter_settings Gravity Forms suggests checkboxes should just be "is"
	 * @var array
	 */
	var $search_operators = array( 'is', 'in', 'not in', 'isnot', 'contains' );

	var $_gf_field_class_name = 'GF_Field_Checkbox';

	var $group = 'standard';

	public function __construct() {
		$this->label = esc_html__( 'Checkbox', 'gravityview' );
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

		// It's not the parent field; it's an input
		if( floor( $field_id ) !== floatval( $field_id ) ) {

			if( $this->is_choice_value_enabled() ) {

				$desc = esc_html__( 'This input has a label and a value. What should be displayed?', 'gravityview' );
				$default = 'value';
				$choices = array(
					'tick' => __( 'A check mark, if the input is checked', 'gravityview' ),
					'value' => __( 'Value of the input', 'gravityview' ),
					'label' => __( 'Label of the input', 'gravityview' ),
				);
			} else {
				$desc = '';
				$default = 'tick';
				$choices = array(
					'tick' => __( 'A check mark, if the input is checked', 'gravityview' ),
					'label' => __( 'Label of the input', 'gravityview' ),
				);
			}

			$field_options['choice_display'] = array(
				'type'    => 'radio',
				'class'   => 'vertical',
				'label'   => __( 'What should be displayed:', 'gravityview' ),
				'value'   => $default,
				'desc'    => $desc,
				'choices' => $choices,
			);
		}

		return $field_options;
	}
}

new GravityView_Field_Checkbox;
