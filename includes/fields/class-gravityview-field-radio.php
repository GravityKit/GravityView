<?php
/**
 * @file class-gravityview-field-radio.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Radio extends GravityView_Field {

	var $name = 'radio';

	var $search_operators = array( 'is', 'in', 'not in', 'isnot', 'contains');

	var $_gf_field_class_name = 'GF_Field_Radio';

	var $group = 'standard';

	public function __construct() {
		$this->label = esc_html__( 'Radio Buttons', 'gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_Radio;
