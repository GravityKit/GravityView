<?php
/**
 * @file class-gravityview-field-multiple-choice.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Multiple_Choice extends GravityView_Field {

	var $name = 'multi_choice';

	var $search_operators = array( 'is', 'in', 'not in', 'isnot', 'contains' );

	var $is_searchable = true;

	var $_gf_field_class_name = 'GF_Field_Multiple_Choice';

	var $group = 'standard';

	var $icon = 'dashicons-list-view';

	public function __construct() {
		$this->label = esc_html__( 'Multiple Choice', 'gk-gravityview' );
		parent::__construct();
	}


	/**
	 * Adds `choice_display` setting to the field
	 *
	 * @since 2.31.0
	 * 
	 * @param array  $field_options
	 * @param string $template_id
	 * @param string $field_id
	 * @param string $context
	 * @param string $input_type
	 *
	 * @return array
	 */
	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		// Set the $_field_id var
		$field_options = parent::field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id );

		if ( $this->is_choice_value_enabled() ) {
			$field_options['choice_display'] = array(
				'type'    => 'radio',
				'value'   => 'value',
				'label'   => __( 'What should be displayed:', 'gk-gravityview' ),
				'desc'    => __( 'This input has a label and a value. What should be displayed?', 'gk-gravityview' ),
				'choices' => array(
					'value' => __( 'Value of the input', 'gk-gravityview' ),
					'label' => __( 'Label of the input', 'gk-gravityview' ),
				),
				'group'   => 'display',
			);
		}

		return $field_options;
	}

}

new GravityView_Field_Multiple_Choice();