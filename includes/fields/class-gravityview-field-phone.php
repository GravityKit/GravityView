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

}

new GravityView_Field_Phone;
