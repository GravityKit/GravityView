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

	var $icon = 'dashicons-yes';

	public function __construct() {
		$this->label = esc_html__( 'Checkbox', 'gk-gravityview' );
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
	 * @since 1.17
	 *
	 * @return array
	 */
	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		// Set the $_field_id var
		$field_options = parent::field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id );

		if ( $this->is_choice_value_enabled() ) {

			$desc    = esc_html__( 'This input has a label and a value. What should be displayed?', 'gk-gravityview' );
			$default = 'value';
			$choices = array(
				'tick'  => __( 'A check mark, if the input is checked', 'gk-gravityview' ),
				'value' => __( 'Value of the input', 'gk-gravityview' ),
				'label' => __( 'Label of the input', 'gk-gravityview' ),
			);
		} else {
			$desc    = '';
			$default = 'tick';
			$choices = array(
				'tick'  => __( 'A check mark, if the input is checked', 'gk-gravityview' ),
				'label' => __( 'Label of the input', 'gk-gravityview' ),
			);
		}

		// It's the parent field, not an input.
		if ( floor( $field_id ) === floatval( $field_id ) ) {
			unset( $choices['tick'] );
		}

		$field_options['choice_display'] = array(
			'type'     => 'radio',
			'class'    => 'vertical',
			'label'    => __( 'What should be displayed:', 'gk-gravityview' ),
			'value'    => $default,
			'desc'     => $desc,
			'choices'  => $choices,
			'group'    => 'display',
			'priority' => 100,
		);

		return $field_options;
	}
}

new GravityView_Field_Checkbox();
