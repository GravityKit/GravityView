<?php
/**
 * @file class-gravityview-field-survey.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Survey extends GravityView_Field {

	var $name = 'survey';

	var $_gf_field_class_name = 'GF_Field_Survey';

	var $is_searchable = false;

	var $group = 'advanced';

	var $icon = 'dashicons-forms';

	public function __construct() {
		$this->label = esc_html__( 'Survey', 'gravityview' );
		parent::__construct();
	}

	/**
	 * Returns the score for a choice at $value
	 *
	 * A sister method to {@see RGFormsModel::get_choice_text}
	 *
	 * @since 2.11
	 *
	 * @param GF_Field_Likert $field
	 * @param string|array $value
	 * @param int|string $input_id ID of the field or input (for example, 7.3 or 7)
	 *
	 * @return mixed|string
	 */
	public static function get_choice_score( $field, $value, $input_id = 0 ) {

		if ( ! $field->gsurveyLikertEnableScoring ) {
			return '';
		}

		if ( ! is_array( $field->choices ) ) {
			return $value;
		}

		foreach ( $field->choices as $choice ) {
			if ( is_array( $value ) && RGFormsModel::choice_value_match( $field, $choice, $value[ $input_id ] ) ) {
				return $choice['score'];
			} else if ( ! is_array( $value ) && RGFormsModel::choice_value_match( $field, $choice, $value ) ) {
				return $choice['score'];
			}
		}

		return is_array( $value ) ? '' : $value;
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		unset( $field_options['search_filter'] );

		if ( 'edit' === $context ) {
			return $field_options;
		}

		$field = \GV\GF_Field::by_id( \GV\GF_Form::by_id( $form_id ), $field_id );
		$input_id = gravityview_get_input_id_from_id( $field_id );
		$add_options = array();

		$glue = apply_filters( 'gravityview/template/field/survey/glue', '; ' );
		$multiple_rows_suffix = sprintf( _x( ' (separated by %s)', 'text added to a label if multiple rows are enabled for the field)', 'gravityview' ), esc_html( trim( $glue ) ) );

		if ( 'likert' === $field->field->inputType ) {

			$show_suffix = $input_id || empty( $field->field->gsurveyLikertEnableMultipleRows );

			$likert_display_options = array(
				'default' => __( 'A table (default Gravity Forms formatting)', 'gravityview' ),
				'text' => __( 'Text value of the selected choice', 'gravityview' ) . ( $show_suffix ? '' : $multiple_rows_suffix ),
			);

			if( $field->field->gsurveyLikertEnableScoring ) {
				$likert_display_options['score'] = __( 'Score value of the selected choice', 'gravityview' ) . ( $show_suffix ? '' : $multiple_rows_suffix );
			}

			// Maintain for back-compatibility
			$add_options['score'] = array(
				'type' => 'hidden',
				'value' => '',
				'group' => 'display',
			);

			$add_options['choice_display'] = array(
				'type' => 'radio',
				'label' => __( 'What should be displayed:', 'gravityview' ),
				'options' => $likert_display_options,
				'desc' => '',
				'group' => 'display',
				'class' => 'block',
				'value' => 'default',
				'merge_tags' => false,
			);
		}

		if( 'checkbox' === $field->field->inputType && $input_id ) {
			$field_options['choice_display'] = array(
				'type'    => 'radio',
				'class'   => 'vertical',
				'label'   => __( 'What should be displayed:', 'gravityview' ),
				'value'   => 'tick',
				'desc'    => '',
				'choices' => array(
					'tick' => __( 'A check mark, if the input is checked', 'gravityview' ),
					'text' => __( 'Text value of the selected choice', 'gravityview' ),
				),
				'group'   => 'display',
				'priority' => 100,
			);
		}

		if ( 'rating' === $field->field->inputType ) {
			$field_options['choice_display'] = array(
				'type'    => 'radio',
				'class'   => 'vertical',
				'label'   => __( 'What should be displayed:', 'gravityview' ),
				'value'   => 'default',
				'desc'    => '',
				'choices' => array(
					'default' => __( 'Text value of the selected choice', 'gravityview' ),
					'stars' => __( 'Stars (default Gravity Forms formatting)', 'gravityview' ),
				),
				'group'   => 'display',
				'priority' => 100,
			);
		}

		return $add_options + $field_options;
	}
}

new GravityView_Field_Survey;
