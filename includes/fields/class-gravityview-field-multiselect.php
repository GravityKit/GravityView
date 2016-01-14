<?php
/**
 * @file class-gravityview-field-multiselect.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_MultiSelect extends GravityView_Field {

	var $name = 'multiselect';

	var $search_operators = array( 'is', 'in', 'not in', 'isnot', 'contains');

	var $_gf_field_class_name = 'GF_Field_MultiSelect';

	var $group = 'standard';

}

new GravityView_Field_MultiSelect;
