<?php

/**
 * @file       class-gravityview-field-image-choice.php
 * @package    GravityView
 * @subpackage includes\fields
 */
class GravityView_Field_Image_Choice extends GravityView_Field {
	var $name = 'image_choice';

	var $search_operators = [ 'is', 'in', 'not in', 'isnot', 'contains' ];

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
	 * @since 2.31
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

		$choices = [
			'label' => __( 'Input label', 'gk-gravityview' ),
			'image' => __( 'Input image', 'gk-gravityview' ),
		];

		if ( $this->is_choice_value_enabled() ) {
			$choices['value'] = __( 'Input value', 'gk-gravityview' );
		}

		$field_options['choice_display'] = [
			'type'    => 'radio',
			'value'   => 'image',
			'label'   => __( 'What should be displayed:', 'gk-gravityview' ),
			'desc' => strtr(
				// Translators: [choice] is replaced by the components that the field has (label, value, and image or label, value).
				__( 'This input displays [choice]. What would you like to show?', 'gk-gravityview' ),
				[
					'[choice]' => $this->is_choice_value_enabled()
						? _x( 'the label, value, and image', 'Options available for displaying the input data', 'gk-gravityview' )
						: _x( 'the label and value', 'Options available for displaying the input data', 'gk-gravityview' )
				]
			),
			'choices' => $choices,
			'group'   => 'display',
		];

		return $field_options;
	}

	/**
	 * Outputs the image choice markup.
	 *
	 * @since 2.31
	 *
	 * @param mixed                            $value The field value.
	 * @param GF_Field_Checkbox|GF_Field_Radio $field The Gravity Forms field (can be either a radio or checkbox field).
	 * @param array                            $form  The current form array.
	 *
	 * @return string The image markup
	 */
	public function output_image_choice( $value, $field, $form ) {
		$choices         = $field->choices;
		$is_entry_detail = $field->is_entry_detail();
		$is_form_editor  = $field->is_form_editor();
		$values          = is_array( $value ) ? $value : [ $value ];
		$output          = '';

		foreach ( $values as $val ) {
			$choice_number = 1;

			foreach ( $choices as $choice ) {
				if ( $choice['value'] != $val ) {
					continue;
				}

				// Taken from `GF_Field_Decorator_Choice_Radio_Markup::get_radio_choices()`.
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
				 * Overrides the image markup for the Image Choice field.
				 *
				 * @since 2.31
				 *
				 * @param string          $image_markup The image markup.
				 * @param array           $choice       The choice array.
				 * @param array           $form         The current form array.
				 * @param GF_Field_Select $field        Gravity Forms Select field.
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
