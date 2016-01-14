<?php
/**
 * @file class-gravityview-field-post-excerpt.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Post_Excerpt extends GravityView_Field {

	var $name = 'post_excerpt';

	var $is_searchable = false;

	var $_gf_field_class_name = 'GF_Field_Post_Excerpt';

	var $group = 'post';

}

new GravityView_Post_Excerpt;
