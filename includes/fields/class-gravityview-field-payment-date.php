<?php
/**
 * @file class-gravityview-field-payment-date.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 1.16
 */

class GravityView_Field_Payment_Date extends GravityView_Field_Date_Created {

	var $name = 'payment_date';

	var $is_searchable = true;

	var $search_operators = array( 'less_than', 'greater_than', 'is', 'isnot' );

	var $group = 'pricing';

	var $_custom_merge_tag = 'payment_date';

	/**
	 * GravityView_Field_Date_Created constructor.
	 */
	public function __construct() {

		// Constructor before the variables because the class extends Date_Created
		parent::__construct();

		$this->label = esc_html__( 'Payment Date', 'gravityview' );
		$this->description = esc_html__( 'The date the payment was received.', 'gravityview' );
	}
}

new GravityView_Field_Payment_Date;
