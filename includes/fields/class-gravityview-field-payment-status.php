<?php

class GravityView_Field_Payment_Status extends GravityView_Field {

	var $name = 'payment_status';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'in', 'not in', 'isnot' );

	var $group = 'pricing';

	var $_custom_merge_tag = 'payment_status';

	/**
	 * GravityView_Field_Date_Created constructor.
	 */
	public function __construct() {
		$this->label = esc_attr__( 'Payment Status', 'gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_Payment_Status;
