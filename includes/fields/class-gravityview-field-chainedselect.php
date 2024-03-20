<?php
/**
 * @file class-gravityview-field-chainedselect.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Chained_Select extends GravityView_Field {

	var $name = 'chainedselect';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot' );

	var $_gf_field_class_name = 'GF_Field_ChainedSelect';

	var $group = 'advanced';

	var $icon = 'dashicons-admin-links';

	public function __construct() {
		$this->label = esc_html__( 'Chained Select', 'gk-gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_Chained_Select();
