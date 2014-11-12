<?php

/**
 * Add custom options for HTML field
 */
class GravityView_Field_Section extends GravityView_Field {

	var $name = 'section';

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		unset ( $field_options['search_filter'], $field_options['show_as_link'] );

		// Set the default CSS class to gv-section, which applies a border and top/bottom margin
		$field_options['custom_class']['value'] = 'gv-section';

		return $field_options;
	}

}

new GravityView_Field_Section;
