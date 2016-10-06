<?php
/**
 * @file class-gravityview-field-payment-status.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 1.16
 */

class GravityView_Field_Payment_Status extends GravityView_Field {

	var $name = 'payment_status';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'in', 'not in', 'isnot' );

	var $group = 'pricing';

	var $_custom_merge_tag = 'payment_status';

	/**
	 * GravityView_Field_Payment_Status constructor.
	 */
	public function __construct() {
		$this->label = esc_html__( 'Payment Status', 'gravityview' );
		$this->description = esc_html__('The current payment status of the entry (ie "Processing", "Failed", "Cancelled", "Approved").', 'gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_Payment_Status;
