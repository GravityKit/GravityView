<?php
/**
 * @file class-gravityview-field-gquiz_score.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Quiz_Score extends GravityView_Field {

	var $name = 'quiz_score';

	var $group = 'advanced';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot', 'greater_than', 'less_than' );

	public function __construct() {
		$this->label = esc_html__( 'Quiz Score', 'gravityview' );
		parent::__construct();
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		if( 'edit' === $context ) {
			return $field_options;
		}

		$new_fields = array(
			'quiz_use_max_score' => array(
				'type' => 'checkbox',
				'label' => __( 'Show Max Score?', 'gravityview' ),
				'desc' => __('Display score as the a fraction: "[score]/[max score]". If unchecked, will display score.', 'gravityview'),
				'value' => true,
				'merge_tags' => false,
			),
		);

		return $new_fields + $field_options;
	}

}

new GravityView_Field_Quiz_Score;
