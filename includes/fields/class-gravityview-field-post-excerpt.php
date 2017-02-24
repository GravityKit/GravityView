<?php
/**
 * @file class-gravityview-field-post-excerpt.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Post_Excerpt extends GravityView_Field {

	var $name = 'post_excerpt';

	var $is_searchable = true;

	var $search_operators = array( 'contains', 'is', 'isnot', 'starts_with', 'ends_with' );

	var $_gf_field_class_name = 'GF_Field_Post_Excerpt';

	var $group = 'post';

	public function __construct() {
		$this->label = esc_html__( 'Post Excerpt', 'gravityview' );
		parent::__construct();
	}
}

new GravityView_Post_Excerpt;
