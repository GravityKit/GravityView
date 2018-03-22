<?php
/**
 * @file class-gravityview-field-transaction-id.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 1.16
 */

class GravityView_Field_Transaction_ID extends GravityView_Field {

	var $name = 'transaction_id';

	var $is_searchable = true;

	var $is_numeric = true;

	var $search_operators = array( 'is', 'isnot', 'starts_with', 'ends_with'  );

	var $group = 'pricing';

	var $_custom_merge_tag = 'transaction_id';

	/**
	 * GravityView_Field_Payment_Amount constructor.
	 */
	public function __construct() {
		$this->label = esc_html__( 'Transaction ID', 'gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_Transaction_ID;
