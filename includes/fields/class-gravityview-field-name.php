<?php
/**
 * @file class-gravityview-field-name.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Name extends GravityView_Field {

	var $name = 'name';

	/** @see GF_Field_Name */
	var $_gf_field_class_name = 'GF_Field_Name';

	var $group = 'advanced';

	public $search_operators = array( 'is', 'isnot', 'contains' );

	var $is_searchable = true;

	public function __construct() {
		$this->label = esc_html__( 'Name', 'gravityview' );
		parent::__construct();
	}

}

new GravityView_Field_Name;
