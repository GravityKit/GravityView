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

}

new GravityView_Field_Password;
