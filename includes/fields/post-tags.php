<?php

/**
 * Add custom options for date fields
 */
class GravityView_Field_Post_Tags extends GravityView_Field {

	var $name = 'post_tags';

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		if( 'edit' === $context ) {
			return $field_options;
		}

		$this->add_field_support('dynamic_data', $field_options );
		$this->add_field_support('link_to_term', $field_options );

		return $field_options;
	}

}

new GravityView_Field_Post_Tags;
