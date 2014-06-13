<?php

abstract class GravityView_Field {

	var $name;

	function __construct() {

		add_filter( sprintf( 'gravityview_template_%s_options', $this->name ), array( &$this, 'field_options' ) );
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {
		return $field_options;
	}

}
