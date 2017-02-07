<?php
/**
 * @file class-gravityview-field-captcha.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Captcha extends GravityView_Field {

	var $name = 'captcha';

	var $is_searchable = false;

	var $_gf_field_class_name = 'GF_Field_CAPTCHA';

	var $group = 'advanced';

	public function __construct() {
		$this->label = esc_html__( 'CAPTCHA', 'gravityview' );
		parent::__construct();
	}

}

new GravityView_Field_Captcha;
