<?php
/**
 * @file class-gravityview-field-page.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Page extends GravityView_Field {

	var $name = 'page';

	var $is_searchable = false;

	var $_gf_field_class_name = 'GF_Field_Page';

	var $group = 'standard';

}

new GravityView_Field_Page;
