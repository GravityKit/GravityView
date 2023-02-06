<?php
/**
 * @file class-gravityview-field-option.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 2.16.3
 */

/**
 * @since 2.16.3
 */
class GravityView_Field_Option extends GravityView_Field {

	var $name = 'option';

	var $is_searchable = true;

	var $is_numeric = false;

	var $search_operators = array( 'is', 'isnot', 'contains', 'in', 'not_in' );

	var $group = 'product';

	var $icon = 'dashicons-cart';

	/** @see GF_Field_Option */
	var $_gf_field_class_name = 'GF_Field_Option';

	/**
	 * @since 1.20
	 */
	public function __construct() {
		$this->label       = esc_html__( 'Option', 'gk-gravityview' );
		$this->description = esc_attr__( 'Options for a specific product field.', 'gk-gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_Option;
