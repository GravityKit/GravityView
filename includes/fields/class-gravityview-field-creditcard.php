<?php
/**
 * @file class-gravityview-field-creditcard.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_CreditCard extends GravityView_Field {

	var $name = 'creditcard';

	var $is_searchable = false;

	var $_gf_field_class_name = 'GF_Field_CreditCard';

	var $group = 'payment';

	public function __construct() {
		$this->label = esc_html__( 'Credit Card', 'gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_CreditCard;
