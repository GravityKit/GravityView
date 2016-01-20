<?php
/**
 * @file class-gravityview-field-hidden.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Hidden extends GravityView_Field {

	var $name = 'hidden';

	var $is_searchable = false;

	var $_gf_field_class_name = 'GF_Field_Hidden';

	var $group = 'standard';

	public function __construct() {
		$this->label = esc_html__( 'Hidden', 'gravityview' );
		parent::__construct();
	}

}

new GravityView_Field_Hidden;
