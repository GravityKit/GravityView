<?php
/**
 * @file class-gravityview-field-shipping.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 2.17
 */

/**
 * @since 2.17
 */
class GravityView_Field_Shipping extends GravityView_Field {

	var $name = 'shipping';

	var $is_searchable = true;

	var $is_numeric = false;

	var $search_operators = array( 'is', 'isnot', 'greater_than', 'less_than' );

	var $group = 'product';

	var $icon = 'dashicons-cart';

	/** @see GF_Field_Shipping */
	var $_gf_field_class_name = 'GF_Field_Shipping';

	public function __construct() {
		$this->label       = esc_html__( 'Shipping', 'gk-gravityview' );
		$this->description = esc_html__( 'The shipping fee for the payment.', 'gk-gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_Shipping();
