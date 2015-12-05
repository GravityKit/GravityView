<?php

class GravityView_Field_Text extends GravityView_Field {

	var $name = 'text';

	var $_gf_field_class_name = 'GF_Field_Text';

	var $search_operators = array( 'contains', 'is', 'isnot', 'starts_with', 'ends_with' );

	var $label = 'Single Line Text';

	public function __construct() {
		$this->label = esc_attr__( 'Single Line Text', 'gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_Text;
