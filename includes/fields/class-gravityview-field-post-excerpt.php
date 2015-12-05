<?php

class GravityView_Post_Excerpt extends GravityView_Field {

	var $name = 'calculation';

	var $is_searchable = false;

	var $_gf_field_class_name = 'GF_Field_Post_Excerpt';

	var $label = 'Excerpt';

	public function __construct() {
		$this->label = esc_attr__( 'Excerpt', 'gravityview' );
		parent::__construct();
	}
}

new GravityView_Post_Excerpt;
