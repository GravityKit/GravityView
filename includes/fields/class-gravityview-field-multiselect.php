<?php

class GravityView_Field_MultiSelect extends GravityView_Field {

	var $name = 'multiselect';

	var $search_operators = array( 'is', 'in', 'not in', 'isnot', 'contains');

	var $_gf_field_class_name = 'GF_Field_MultiSelect';

	var $label = 'Multi Select';

	public function __construct() {
		$this->label = esc_attr__( 'Multi Select', 'gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_MultiSelect;
