<?php
/**
 * @file class-gravityview-field-calculation.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Calculation extends GravityView_Field {

	var $name = 'calculation';

	var $is_searchable = false;

	var $group = 'pricing';

	var $_gf_field_class_name = 'GF_Field_Calculation';

}

new GravityView_Field_Calculation;
