<?php

class GravityView_Field_Post_Custom_Field extends GravityView_Field {

	var $name = 'post_custom_field';

	var $is_searchable = true;

	var $_gf_field_class_name = 'GF_Field_Post_Custom_Field';

	var $label = 'Custom Field';

	public function __construct() {
		$this->label = esc_attr__( 'Custom Field', 'gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_Post_Custom_Field;
