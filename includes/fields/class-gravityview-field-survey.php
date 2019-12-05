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

		$add_options = array();

		if ( $field->field->inputType === 'likert' && $field->field->gsurveyLikertEnableScoring ) {
			$add_options['score'] = array(
				'type' => 'checkbox',
				'label' => __( 'Show score', 'gravityview' ),
				'desc' => __( 'Display likert score as a simple number.', 'gravityview' ),
				'value' => false,
				'merge_tags' => false,
			);
		}

		return $add_options + $field_options;
	}
}

new GravityView_Field_Survey;
