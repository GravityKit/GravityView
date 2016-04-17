<?php
/**
 * @file class-gravityview-field-gquiz_score.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Quiz extends GravityView_Field {

	var $name = 'quiz';

	var $group = 'advanced';

	public function __construct() {
		$this->label = esc_html__( 'Quiz', 'gravityview' );
		parent::__construct();
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		if( 'edit' === $context ) {
			return $field_options;
		}

		$new_fields = array(
			'quiz_show_explanation' => array(
				'type' => 'checkbox',
				'label' => __( 'Show Answer Explanation?', 'gravityview' ),
				'desc' => __('If the field has an answer explanation, show it?', 'gravityview'),
				'value' => false,
				'merge_tags' => false,
			),
		);

		return $new_fields + $field_options;
	}

}

new GravityView_Field_Quiz;
