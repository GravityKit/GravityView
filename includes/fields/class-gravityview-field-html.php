<?php
/**
 * @file class-gravityview-field-html.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Add custom options for HTML field
 */
class GravityView_Field_HTML extends GravityView_Field {

	var $name = 'html';

	var $is_searchable = false;

	var $is_sortable = false;

	var $_gf_field_class_name = 'GF_Field_HTML';

	var $group = 'standard';

	public function __construct() {
		$this->label = esc_html__( 'HTML', 'gravityview' );
		parent::__construct();
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		unset ( $field_options['search_filter'], $field_options['show_as_link'] );

		return $field_options;
	}

}

new GravityView_Field_HTML;
