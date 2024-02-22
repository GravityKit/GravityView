<?php
/**
 * @file class-gravityview-field-multiselect.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_MultiSelect extends GravityView_Field {

	var $name = 'multiselect';

	/**
	 * @see GFCommon::get_field_filter_settings Gravity Forms suggests checkboxes should just be "contains"
	 * @var array
	 */
	var $search_operators = array( 'is', 'in', 'not in', 'isnot', 'contains' );

	var $is_searchable = true;

	/** @see GF_Field_MultiSelect */
	var $_gf_field_class_name = 'GF_Field_MultiSelect';

	var $group = 'standard';

	var $icon = 'dashicons-editor-ul';

	public function __construct() {
		$this->label = esc_html__( 'Multi Select', 'gk-gravityview' );
		parent::__construct();
	}

	/**
	 * Add `choice_display` setting to the field
	 *
	 * @param array  $field_options
	 * @param string $template_id
	 * @param string $field_id
	 * @param string $context
	 * @param string $input_type
	 *
	 * @since 2.0
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

new GravityView_Field_MultiSelect();
