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
			// translators: %s is replaced by the components that the field has (label, value, and image or label, value)
			'desc'    => sprintf( __( 'This input has a %s. What should be displayed?', 'gk-gravityview' ), $this->is_choice_value_enabled() ? _x( 'label, value, and image', 'These are a list of choices for what to to display for the current input.', 'gk-gravityview' ) : _x( 'label and value', 'These are a list of choices for what to to display for the current input.', 'gk-gravityview' ) ),
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
		$choices         = $field->choices;
		$is_entry_detail = $field->is_entry_detail();
		$is_form_editor  = $field->is_form_editor();
		$output          = '';

		$values = is_array( $value ) ? $value : array( $value );

		foreach ( $values as $val ) {
			$choice_number = 1;

			foreach ( $choices as $choice_id => $choice ) {
				if ( $choice['value'] != $val ) {
					continue;
				}

				// Taken from GF_Field_Decorator_Choice_Radio_Markup::get_radio_choices()
				if ( $choice_number % 10 == 0 ) {
					$choice_number++;
				}

				if ( $is_entry_detail || $is_form_editor || $form['id'] == 0 ) {
					$id = $field->id . '_' . $choice_number;
				} else {
					$id = $form['id'] . '_' . $field->id . '_' . $choice_number;
				}

				$decorator = new ChoiceDecorator( $field );

				/**
				 * Override the image markup for the image choice field.
				 *
				 * @filter `gravityview/fields/image_choice/image_markup`
				 *
				 * @since TBD
				 *
				 * @param string          $image_markup The image markup
				 * @param array           $choice       The choice array
				 * @param array           $form         The current form array
				 * @param GF_Field_Select $field        Gravity Forms Select field
				 */
				$image_markup = apply_filters(
					'gravityview/fields/image_choice/image_markup',
					$decorator->get_image_markup( $choice, $id, $choice_number, $form ),
					$choice,
					$form,
					$field,
				);

				$output .= $image_markup;

				break;
			}
		}

		return $output;
	}


}

new GravityView_Field_Image_Choice();
