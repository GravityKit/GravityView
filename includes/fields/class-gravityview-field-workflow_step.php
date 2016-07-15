<?php
/**
 * @file class-gravityview-field-workflow_step.php
 * @since 1.17.2
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Workflow_Step extends GravityView_Field {

	var $name = 'workflow_step';

	var $group = 'meta';

	public function __construct() {
		$this->label = esc_html__( 'Workflow Step', 'gravityview' );
		parent::__construct();
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		return $field_options;
	}

}

new GravityView_Field_Workflow_Step;
