<?php
/**
 * @file class-gravityview-field-id.php
 * @since 2.10
 * @subpackage includes\fields
 * @package GravityView
 */

class GravityView_Field_IP extends GravityView_Field {

	var $name = 'ip';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot', 'contains' );

	var $group = 'meta';

	var $icon = 'dashicons-laptop';

	var $is_numeric = true;

	public function __construct() {
		$this->label       = __( 'User IP', 'gk-gravityview' );
		$this->description = __( 'The IP Address of the user who created the entry.', 'gk-gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_IP();
