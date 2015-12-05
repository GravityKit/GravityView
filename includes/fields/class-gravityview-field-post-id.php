<?php

/**
 * Add custom options for Post ID fields
 *
 * @since 1.7
 */
class GravityView_Field_Post_ID extends GravityView_Field {

	var $name = 'post_id';

	var $search_operators = array( 'is', 'isnot', 'greater_than', 'less_than' );

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		$this->add_field_support('link_to_post', $field_options );

		return $field_options;
	}

}

new GravityView_Field_Post_ID;
