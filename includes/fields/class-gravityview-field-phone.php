<?php
/**
 * @file class-gravityview-field-phone.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Phone extends GravityView_Field {

	var $name = 'phone';

	var $is_searchable = false;

	var $_gf_field_class_name = 'GF_Field_Phone';

	var $group = 'advanced';

	public function __construct() {
		$this->label = esc_html__( 'Phone', 'gravityview' );
		parent::__construct();
	}

}

new GravityView_Field_Phone;
