<?php

/**
 * Add custom options for Post Image fields
 */
class GravityView_Field_Post_Image extends GravityView_Field {

	var $name = 'post_image';

	var $_gf_field_class_name = 'GF_Field_Post_Image';

	var $label = 'Post Image';

	public function __construct() {
		$this->label = __( 'Post Image', 'gravityview' );
		parent::__construct();
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		unset ( $field_options['search_filter'] );

		if( 'edit' === $context ) {
			return $field_options;
		}

		$this->add_field_support('link_to_post', $field_options );

		// @since 1.5.4
		$this->add_field_support('dynamic_data', $field_options );

		return $field_options;
	}

}

new GravityView_Field_Post_Image;
