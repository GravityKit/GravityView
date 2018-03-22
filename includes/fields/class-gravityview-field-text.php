<?php
/**
 * @file class-gravityview-field-text.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Text extends GravityView_Field {

	var $name = 'text';

	var $_gf_field_class_name = 'GF_Field_Text';

	var $is_searchable = true;

	var $search_operators = array( 'contains', 'is', 'isnot', 'starts_with', 'ends_with' );

	var $group = 'standard';

	public function __construct() {
		$this->label = esc_html__( 'Single Line Text', 'gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_Text;
