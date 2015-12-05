<?php

class GravityView_Field_Page extends GravityView_Field {

	var $name = 'page';

	var $is_searchable = false;

	var $_gf_field_class_name = 'GF_Field_Page';

	var $label = 'Page';

	public function __construct() {
		$this->label = esc_attr__( 'Page', 'gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_Page;
