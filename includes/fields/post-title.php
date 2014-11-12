<?php

/**
 * Add custom options for date fields
 */
class GravityView_Field_Post_Title extends GravityView_Field {

	var $name = 'post_title';

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		if( 'edit' === $context ) {
			return $field_options;
		}

		$this->add_field_support('link_to_post', $field_options );

		$this->add_field_support('dynamic_data', $field_options );

		return $field_options;
	}

}

new GravityView_Field_Post_Title;
