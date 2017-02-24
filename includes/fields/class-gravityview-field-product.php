<?php
/**
 * @file class-gravityview-field-product.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 1.20
 */

/**
 * @since 1.20
 */
class GravityView_Field_Product extends GravityView_Field {

	var $name = 'product';

	var $is_searchable = true;

	var $is_numeric = false;

	var $search_operators = array( 'is', 'isnot', 'contains' );

	var $group = 'product';

	/** @see GF_Field_Product */
	var $_gf_field_class_name = 'GF_Field_Product';

	/**
	 * @since 1.20
	 */
	public function __construct() {

		add_filter( 'gravityview/edit_entry/field_blacklist', array( $this, 'edit_entry_field_blacklist' ), 10, 2 );

		add_filter( 'gravityview/edit_entry/after_update', array( $this, 'clear_product_info_cache' ), 10, 3 );

		parent::__construct();
	}

	/**
	 * If the edited entry has a product field and the fields are shown, remove entry purchase cache
	 *
	 * @since 1.20
	 *
	 * @param array $form Gravity Forms array
	 * @param int $entry_id Gravity Forms entry ID
	 * @param GravityView_Edit_Entry_Render $Edit_Entry_Render
	 *
	 * @return void
	 */
	function clear_product_info_cache( $form = array(), $entry_id = 0, $Edit_Entry_Render = null ) {

		if( $this->should_hide_product_fields( $Edit_Entry_Render->entry ) ) {
			return;
		}

		// Clear the purchase details so we can re-calculate them
		if ( GVCommon::has_product_field( $form ) ) {
			gform_delete_meta( $entry_id, 'gform_product_info__' );
			gform_delete_meta( $entry_id, 'gform_product_info__1' );
			gform_delete_meta( $entry_id, 'gform_product_info_1_' );
			gform_delete_meta( $entry_id, 'gform_product_info_1_1' );
		}

	}

	/**
	 * Maybe add Product fields to the Edit Entry blacklist
	 *
	 * @since 1.20
	 *
	 * @param array $blacklist Array of field types not to be shown in the Edit Entry form
	 * @param array $entry Gravity Forms entry array
	 *
	 * @return array Blacklist with product field types added, if should not be shown
	 */
	public function edit_entry_field_blacklist( $blacklist = array(), $entry = array() ) {

		if ( $this->should_hide_product_fields( $entry ) ) {
			$blacklist = array_merge( $blacklist, GVCommon::get_product_field_types() );
		}

		return $blacklist;
	}

	/**
	 * In Edit Entry, should Product fields be hidden? If entry has transaction data, they should be. Otherwise, no.
	 *
	 * @since 1.20
	 *
	 * @param array $entry Current Gravity Forms entry being edited
	 *
	 * @return bool True: hide product fields; False: show product fields
	 */
	public function should_hide_product_fields( $entry = array() ) {

		$has_transaction_data = GVCommon::entry_has_transaction_data( $entry );

		/**
		 * @filter `gravityview/edit_entry/hide-product-fields` Hide product fields from being editable
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
