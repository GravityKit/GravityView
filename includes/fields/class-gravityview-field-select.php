<?php
/**
 * @file class-gravityview-field-select.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Select extends GravityView_Field {

	var $name = 'select';

	var $is_searchable = false;

	var $_gf_field_class_name = 'GF_Field_Select';

	var $group = 'standard';

	public function __construct() {
		$this->label = esc_html__( 'Select', 'gravityview' );
		parent::__construct();
	}

}

new GravityView_Field_Select;
