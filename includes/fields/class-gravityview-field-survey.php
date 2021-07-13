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
				'default' => __( 'Table (default Gravity Forms formatting)', 'gravityview' ),
				'text' => __( 'Text value of the selected choice', 'gravityview' ) . ( $show_suffix ? '' : $multiple_rows_suffix ),
			);

			if( $field->field->gsurveyLikertEnableScoring ) {
				$likert_display_options['score'] = __( 'Score value of the selected choice', 'gravityview' ) . ( $show_suffix ? '' : $multiple_rows_suffix );
			}

			$add_options['choice_display'] = array(
				'type' => 'radio',
				'label' => __( 'Show as', 'gravityview' ),
				'options' => $likert_display_options,
				'desc' => __( 'How would you like to display the likert survey response?', 'gravityview' ),
				'group' => 'display',
				'class' => 'block',
				'value' => \GV\Utils::get( $field_options, 'score', 'default' ),
				'merge_tags' => false,
				'tooltip' => '',
				'article' => array(
					'id' => '5c9d338a2c7d3a1544617f9b',
					'url' => 'https://docs.gravityview.co/article/570-sorting-by-multiple-columns',
				),
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
					'label' => __( 'Label of the input', 'gravityview' ),
				),
				'group'   => 'display',
				'priority' => 100,
			);
		}

		return $add_options + $field_options;
	}
}

new GravityView_Field_Survey;
