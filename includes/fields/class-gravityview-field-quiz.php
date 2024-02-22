<?php
/**
 * @file class-gravityview-field-gquiz.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Quiz extends GravityView_Field {

	var $name = 'quiz';

	var $group = 'advanced';

	var $icon = 'dashicons-forms';

	public function __construct() {
		$this->label = esc_html__( 'Quiz', 'gk-gravityview' );
		parent::__construct();
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		if ( 'edit' === $context ) {
			return $field_options;
		}

		$new_fields = array(
			'quiz_show_explanation' => array(
				'type'       => 'checkbox',
				'label'      => __( 'Show Answer Explanation?', 'gk-gravityview' ),
				'desc'       => __( 'If the field has an answer explanation, show it?', 'gk-gravityview' ),
				'value'      => false,
				'merge_tags' => false,
			),
		);

		return $new_fields + $field_options;
	}
}

new GravityView_Field_Quiz();
