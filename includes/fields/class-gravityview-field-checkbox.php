<?php
/**
 * @file class-gravityview-field-checkbox.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Checkbox extends GravityView_Field {

	var $name = 'checkbox';

	var $search_operators = array( 'is', 'in', 'not in', 'isnot', 'contains');

	var $_gf_field_class_name = 'GF_Field_Checkbox';

	var $group = 'standard';

	public function __construct() {
		$this->label = esc_html__( 'Checkbox', 'gravityview' );
		parent::__construct();
	}

}

new GravityView_Field_Checkbox;
