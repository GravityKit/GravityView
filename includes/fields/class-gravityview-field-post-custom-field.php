<?php
/**
 * @file class-gravityview-field-post-custom-field.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Post_Custom_Field extends GravityView_Field {

	var $name = 'post_custom_field';

	var $is_searchable = true;

	var $_gf_field_class_name = 'GF_Field_Post_Custom_Field';

	var $group = 'post';

	public function __construct() {
		$this->label = esc_html__( 'Post Custom Field', 'gravityview' );
		parent::__construct();
	}

}

new GravityView_Field_Post_Custom_Field;
