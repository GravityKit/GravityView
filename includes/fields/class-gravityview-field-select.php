<?php

class GravityView_Field_Select extends GravityView_Field {

	var $name = 'select';

	var $is_searchable = false;

	var $_gf_field_class_name = 'GF_Field_Select';

	var $label = 'Drop Down';

	public function __construct() {

		$this->label = esc_attr__( 'Drop Down', 'gravityview' );

		parent::__construct();
	}
}

new GravityView_Field_Select;
