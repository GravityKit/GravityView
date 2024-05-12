<?php
/**
 * @file class-gravityview-field-quantity.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 2.17
 */

/**
 * @since 2.17
 */
class GravityView_Field_Quantity extends GravityView_Field {

	var $name = 'quantity';

	var $is_searchable = true;

	var $is_numeric = false;

	var $search_operators = array( 'is', 'isnot', 'greater_than', 'less_than' );

	var $group = 'product';

	var $icon = 'dashicons-cart';

	/** @see GF_Field_Quantity */
	var $_gf_field_class_name = 'GF_Field_Quantity';

	public function __construct() {
		$this->label       = esc_html__( 'Quantity', 'gk-gravityview' );
		$this->description = esc_html__( 'The quantity of a specific product field.', 'gk-gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_Quantity();
