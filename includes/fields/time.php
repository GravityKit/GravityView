<?php

/**
 * Add custom options for date fields
 */
class GravityView_Field_Time extends GravityView_Field {

	var $name = 'time';

	function field_options( $field_options, $template_id = '', $field_id = '', $context = '', $input_type = '' ) {

		if( 'edit' === $context ) {
			return $field_options;
		}

		add_filter('gravityview_date_format', array( $this, 'date_format' ) );
		$this->add_field_support('date_display', $field_options );
		remove_filter('gravityview_date_format', array( $this, 'date_format' ) );

		return $field_options;
	}

	function date_format() {
		return 'h:iA';
	}

}

new GravityView_Field_Time;
