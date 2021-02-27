<?php
/**
 * @file class-gravityview-field-id.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 2.10
 */

class GravityView_Field_ID extends GravityView_Field {

	var $name = 'id';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot', 'greater_than', 'less_than', 'in', 'not_in' );

	var $group = 'meta';

	var $icon = 'dashicons-code-standards';

	var $is_numeric = true;

	public function __construct() {
		$this->label = esc_html__( 'Entry ID', 'gravityview' );
	    $this->description = __('The unique ID of the entry.', 'gravityview');
		parent::__construct();
	}
}

new GravityView_Field_ID;
