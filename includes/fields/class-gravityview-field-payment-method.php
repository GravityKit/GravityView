<?php
/**
 * @file class-gravityview-field-payment-method.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 1.16
 */

class GravityView_Field_Payment_Method extends GravityView_Field {

	var $name = 'payment_method';

	var $is_searchable = true;

	var $is_numeric = false;

	var $search_operators = array( 'is', 'isnot', 'contains' );

	var $group = 'pricing';

	var $_custom_merge_tag = 'payment_method';

	/**
	 * GravityView_Field_Date_Created constructor.
	 */
	public function __construct() {
		$this->label = esc_html__( 'Payment Method', 'gravityview' );
		$this->description = esc_html__( 'The way the entry was paid for (ie "Credit Card", "PayPal", etc.)', 'gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_Payment_Method;
