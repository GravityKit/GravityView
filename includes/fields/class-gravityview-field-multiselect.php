<?php
/**
 * @file class-gravityview-field-multiselect.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_MultiSelect extends GravityView_Field {

	var $name = 'multiselect';

	/**
	 * @see GFCommon::get_field_filter_settings Gravity Forms suggests checkboxes should just be "contains"
	 * @var array
	 */
	var $search_operators = array( 'is', 'in', 'not in', 'isnot', 'contains');

	var $is_searchable = true;

	/** @see GF_Field_MultiSelect */
	var $_gf_field_class_name = 'GF_Field_MultiSelect';

	var $group = 'standard';

	public function __construct() {
		$this->label = esc_html__( 'Multi Select', 'gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_MultiSelect;
