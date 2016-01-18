<?php
/**
 * @file class-gravityview-field-password.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Password extends GravityView_Field {

	var $name = 'password';

	var $is_searchable = false;

	var $_gf_field_class_name = 'GF_Field_Password';

	var $group = 'advanced';

	public function __construct() {
		$this->label = esc_html__( 'Password', 'gravityview' );
		parent::__construct();
	}

}

new GravityView_Field_Password;
