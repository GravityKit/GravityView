<?php
/**
 * @file class-gravityview-field-image-choice.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Image_Choice extends GravityView_Field {

	var $name = 'image_choice';

	var $search_operators = array( 'is', 'in', 'not in', 'isnot', 'contains' );

	var $is_searchable = true;

	var $_gf_field_class_name = 'GF_Field_Image_Choice';

	var $group = 'standard';

	var $icon = 'dashicons-images-alt';

	public function __construct() {
		$this->label = esc_html__( 'Image Choice', 'gk-gravityview' );
		parent::__construct();
	}

	/**
	 * Adds `choice_display` setting to the field
	 *
	 * @since TBD
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

		$field_options = parent::field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id );

		$choices = array(
			'label' => __( 'Label of the input', 'gk-gravityview' ),
			'image' => __( 'Image of the input', 'gk-gravityview' ),
		);

		if ( $this->is_choice_value_enabled() ) {
			$choices['value'] = __( 'Value of the input', 'gk-gravityview' );
		}

		$field_options['choice_display'] = array(
			'type'    => 'radio',
			'value'   => 'image',
			'label'   => __( 'What should be displayed:', 'gk-gravityview' ),
			'desc'    => sprintf( __( 'This input has a label%s and an image. What should be displayed?', 'gk-gravityview' ), $this->is_choice_value_enabled() ? __( ', value', 'gk-gravityview' ) : '' ),
			'choices' => $choices,
			'group'   => 'display',
		);

		return $field_options;
	}

	/**
	 * Outputs the image choice markup.
	 *
	 * @since TBD
	 *
	 * @param mixed                $value The field value
	 * @param GF_Field_Select      $field Gravity Forms Select field
	 * @param array                $form The current form array
	 * @param array                $entry GF Entry
	 * @param \GV\Template_Context $gravityview The context
	 *
	 * @return string The image markup
	 */
	public function output_image_choice( $value, $field, $form ) {
		$choices = $field->choices;
		$output  = '';

		$values = is_array( $value ) ? $value : array( $value );

		foreach ( $values as $val ) {
			foreach ( $choices as $choice ) {
				if ( $choice['value'] != $val ) {
					continue;
				}
				$decorator = new ChoiceDecorator( $field );
				/**
				 * Override the image markup for the image choice field.
				 *
				 * @since TBD
				 *
				 * @param string $image_markup The image markup
				 * @param array $choice The choice array
				 * @param array $form The current form array
				 * @param GF_Field_Select $field Gravity Forms Select field
				 */
				$image_markup = apply_filters(
					'gravityview/fields/image_choice/image_markup',
					$decorator->get_image_markup( $choice, $form ),
					$choice,
					$form,
					$field,
				);
				$output      .= $image_markup;
				break;
			}
		}

		return $output;
	}


}

new GravityView_Field_Image_Choice();
