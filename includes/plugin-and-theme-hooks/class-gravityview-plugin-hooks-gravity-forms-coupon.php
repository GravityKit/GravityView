<?php
/**
 * Add Gravity Forms Coupon compatibility to Edit Entry
 *
 * @file      class-gravityview-plugin-hooks-gravity-forms-coupon.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      https://gravityview.co
 * @copyright Copyright 2017, Katz Web Services, Inc.
 *
 * @since 1.20
 */

/**
 * @since 1.20
 */
class GravityView_Plugin_Hooks_Gravity_Forms_Coupon extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @var string gf_coupons() wrapper function only exists in Version 2.x; don't want to support 1.x
	 * @since 1.20
	 */
	protected $function_name = 'gf_coupons';

	/**
	 * @since 1.20
	 */
	protected function add_hooks() {
		parent::add_hooks();

		add_filter( 'gravityview/edit_entry/field_blacklist', array( $this, 'edit_entry_field_blacklist' ), 10, 2 );
		add_filter( 'gravityview/edit_entry/field_value_coupon', array( $this, 'edit_entry_field_value' ), 10, 3 );
	}

	/**
	 * Should Coupon fields be hidden in Edit Entry?
	 *
	 * @since 1.20
	 *
	 * @param array $entry Entry being edited in Edit Entry, if set
	 *
	 * @return bool True: Yes, show coupon fields in Edit Entry; False: no, don't show Coupon fields
	 */
	public function should_hide_coupon_fields( $entry = array() ) {

		$has_transaction_data = GVCommon::entry_has_transaction_data( $entry );

		/**
		 * @filter `gravityview/edit_entry/hide-coupon-fields` Should Coupon fields be hidden in Edit Entry?
		 * @since 1.20
		 * @param bool $has_transaction_data If true (the Entry has transaction data), hide the fields. Otherwise (false), show the Coupon field
		 */
		$hide_coupon_fields = apply_filters( 'gravityview/edit_entry/hide-coupon-fields', $has_transaction_data );

		return (bool) $hide_coupon_fields;
	}

	/**
	 * Adds Coupon fields to Edit Entry field blacklist
	 *
	 * @since 1.20
	 *
	 * @param array $blacklist Array of field types
	 * @param array $entry Entry array of entry being edited in Edit Entry
	 *
	 * @return array Blacklist array, with coupon possibly added
	 */
	public function edit_entry_field_blacklist( $blacklist = array(), $entry = array() ) {

		if ( $this->should_hide_coupon_fields( $entry ) ) {
			$blacklist[] = 'coupon';
		}

		return $blacklist;
	}

	/**
	 * Set the coupon values for entries that have coupons applied
	 *
	 * Uses $_POST hacks
	 *
	 * @since 1.20
	 *
	 * @param string $value
	 * @param GF_Field_Coupon $field
	 * @param GravityView_Edit_Entry_Render $Edit_Entry_Render
	 *
	 * @return string $value is returned unmodified. Only $_POST is modified.
	 */
	public function edit_entry_field_value( $value, $field, $Edit_Entry_Render ) {

		if ( $this->should_hide_coupon_fields() ) {
			return $value;
		}

		$entry = $Edit_Entry_Render->entry;
		$form  = $Edit_Entry_Render->form;

		$coupon_codes = gf_coupons()->get_submitted_coupon_codes( $form, $entry );

		// Entry has no coupon codes
		if ( ! $coupon_codes ) {
			return $value;
		}

		// No coupons match the codes provided
		$discounts = gf_coupons()->get_coupons_by_codes( $coupon_codes, $form );

		if( ! $discounts ) {
			return $value;
		}

		/**
		 * @hack Fake POST data so that the data gets pre-filled. Both are needed.
		 * @see GF_Field_Coupon::get_field_input
		 */
		$_POST = ! isset( $_POST ) ? array() : $_POST;
		$_POST[ 'gf_coupons_' . $form['id'] ] = json_encode( (array) $discounts );
		$_POST[ 'input_' . $field->id ] = implode( ',', $coupon_codes );

		return $value;
	}

}

new GravityView_Plugin_Hooks_Gravity_Forms_Coupon;