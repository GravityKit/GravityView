<?php

/**
 * Add custom options for HTML field
 */
class GravityView_Field_HTML extends GravityView_Field {

	var $name = 'html';

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		unset ( $field_options['search_filter'], $field_options['show_as_link'] );

		return $field_options;
	}

}

new GravityView_Field_HTML;
