<?php
/**
 * @file class-gravityview-field-page.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Page extends GravityView_Field {

	var $name = 'page';

	var $is_searchable = false;

	/** @see GF_Field_Page */
	var $_gf_field_class_name = 'GF_Field_Page';

	var $group = 'standard';

	public function __construct() {
		$this->label = esc_html__( 'Page', 'gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_Page;
