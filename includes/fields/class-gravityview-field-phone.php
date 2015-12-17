<?php

class GravityView_Field_Phone extends GravityView_Field {

	var $name = 'phone';

	var $is_searchable = false;

	var $_gf_field_class_name = 'GF_Field_Phone';

	var $group = 'advanced';

	var $label = 'Phone';

	public function __construct() {
		$this->label = esc_attr__( 'Phone', 'gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_Phone;
