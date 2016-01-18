<?php
/**
 * @file class-gravityview-field-currency.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 1.16
 */

class GravityView_Field_Currency extends GravityView_Field {

	var $name = 'currency';

	var $is_searchable = true;

	var $is_numeric = true;

	var $search_operators = array( 'is', 'isnot' );

	var $group = 'pricing';

	var $_custom_merge_tag = 'currency';

	/**
	 * GravityView_Field_Currency constructor.
	 */
	public function __construct() {
		$this->label = esc_html__( 'Currency', 'gravityview' );
		parent::__construct();
	}
}

new GravityView_Field_Currency;
