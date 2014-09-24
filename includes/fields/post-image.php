<?php

/**
 * Add custom options for Post Image fields
 */
class GravityView_Field_Post_Image extends GravityView_Field {

	var $name = 'post_image';

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		unset ( $field_options['search_filter'] );

		$this->add_field_support('link_to_post', $field_options );

		return $field_options;
	}

}

new GravityView_Field_Post_Image;
