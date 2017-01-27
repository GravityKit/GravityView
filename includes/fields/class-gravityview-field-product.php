<?php
/**
 * @file class-gravityview-field-payment-amount.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 1.16
 */

class GravityView_Field_Product extends GravityView_Field {

	var $name = 'product';

	var $is_searchable = true;

	var $is_numeric = false;

	var $search_operators = array( 'is', 'isnot', 'contains' );

	var $group = 'product';

	/** @see GF_Field_Product */
	var $_gf_field_class_name = 'GF_Field_Product';

	public function __construct() {

		add_filter( 'gravityview/edit_entry/field_blacklist', array( $this, 'edit_entry_field_blacklist' ), 10, 2 );

		parent::__construct();
	}

	/**
	 * Maybe add Product fields to the Edit Entry blacklist
	 *
	 * @param array $blacklist Array of field types not to be shown in the Edit Entry form
	 * @param array $entry Gravity Forms entry array
	 *
	 * @return array Blacklist with product field types added, if should not be shown
	 */
	public function edit_entry_field_blacklist( $blacklist = array(), $entry = array() ) {

		if ( $this->should_hide_product_fields( $entry ) ) {
			$blacklist += GVCommon::get_product_field_types();
		}

		return $blacklist;
	}

	/**
	 * In Edit Entry, should Product fields be hidden? If entry has transaction data, they should be. Otherwise, no.
	 *
	 * @param array $entry Current Gravity Forms entry being edited
	 *
	 * @return bool True: hide product fields; False: show product fields
	 */
	public function should_hide_product_fields( $entry = array() ) {

		$has_transaction_data = GVCommon::entry_has_transaction_data( $entry );

		/**
		 * @filter `gravityview/edit_entry/hide-product-fields` Hide product fields from being editable.
		 * @since 1.9.1
		 * @since 1.20 Changed default from false to whether or not entry has transaction data
		 * @see GVCommon::entry_has_transaction_data()
		 * @param boolean $hide_product_fields Whether to hide product fields in the editor. Uses $entry data to determine.
		 */
		$hide_product_fields = (bool) apply_filters( 'gravityview/edit_entry/hide-product-fields', $has_transaction_data );

		return $hide_product_fields;
	}
}

new GravityView_Field_Product;
