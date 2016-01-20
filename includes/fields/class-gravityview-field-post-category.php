<?php
/**
 * @file class-gravityview-field-post-category.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Post_Category extends GravityView_Field {

	var $name = 'post_category';

	var $search_operators = array( 'is', 'in', 'not in', 'isnot', 'contains');

	var $_gf_field_class_name = 'GF_Field_Post_Category';

	var $group = 'post';

	public function __construct() {
		$this->label = esc_html__( 'Post Category', 'gravityview' );
		parent::__construct();
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		if( 'edit' === $context ) {
			return $field_options;
		}

		$this->add_field_support('dynamic_data', $field_options );
		$this->add_field_support('link_to_term', $field_options );

		return $field_options;
	}

}

new GravityView_Field_Post_Category;
