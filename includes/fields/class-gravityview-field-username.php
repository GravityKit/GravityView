<?php
/**
 * @file class-gravityview-field-username.php
 * @since 2.17
 * @subpackage includes\fields
 * @package GravityView
 */

class GravityView_Field_Username extends GravityView_Field {

	var $name = 'username';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot', 'in', 'notin', 'contains' );

	var $group = 'advanced';

	var $icon = 'dashicons-admin-users';

	var $is_numeric = false;

	/** @see GF_Field_Username */
	var $_gf_field_class_name = 'GF_Field_Username';

	public function __construct() {
		$this->label       = esc_html__( 'Username', 'gk-gravityview' );
		$this->description = esc_html__( 'The username of the user who created the entry.', 'gk-gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_Username();
