<?php
/**
 * @file class-gravityview-field-total.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 1.20
 */

class GravityView_Field_Total extends GravityView_Field {

	var $name = 'total';

	var $is_searchable = true;

	var $is_numeric = true;

	var $search_operators = array( 'is', 'isnot', 'greater_than', 'less_than', 'contains' );

	var $group = 'product';

	/** @see GF_Field_Total */
	var $_gf_field_class_name = 'GF_Field_Total';

	public function __construct() {
		$this->label = esc_html__( 'Total', 'gravityview' );

		add_filter( 'gravityview/edit_entry/after_update', array( $this, 'edit_entry_recalculate_totals' ), 10, 3 );

		add_filter( 'gravityview_blacklist_field_types', array( $this, 'add_to_blacklist' ), 10, 2 );

		parent::__construct();
	}

	/**
	 * Prevent the Total fields from being displayed in the Edit Entry configuration screen -- for now
	 *
	 * Gravity Forms forms need to know all the pricing information available to calculate a Total.
	 *
	 * If you have an Edit Entry field with just two fields (Quantity and Total), the Total will not be able to calculate
	 * without the Product field, and possibly the Option, Shipping, and Coupon fields.
	 *
	 * The only options currently available are: show the whole form, or don't show the Total
	 *
	 * @since 1.20
	 *
	 * @todo Support Total fields in Edit Entry configuration
	 *
	 * @param array $blacklist Array of field types not able to be added to Edit Entry
	 * @param  string|null $context Context
	 *
	 * @return array Blacklist, with "total" added. If not edit context, original field blacklist. Otherwise, blacklist including total.
	 */
	public function add_to_blacklist( $blacklist = array(), $context = NULL  ){

		if( empty( $context ) || $context !== 'edit' ) {
			return $blacklist;
		}

		$blacklist[] = 'total';

		return $blacklist;
	}

	/**
	 * If entry has totals fields, recalculate them
	 *
	 * @since 1.20
	 *
	 * @param array $form Gravity Forms form array
	 * @param int $entry_id Gravity Forms Entry ID
	 * @param GravityView_Edit_Entry_Render $Edit_Entry_Render
	 *
	 * @return void
	 */
	function edit_entry_recalculate_totals( $form = array(), $entry_id = 0, $Edit_Entry_Render = null ) {

		$original_form = GFAPI::get_form( $form['id'] );

		$total_fields = GFCommon::get_fields_by_type( $original_form, 'total' );

		//saving total field as the last field of the form.
		if ( ! empty( $total_fields ) ) {

			$entry = GFAPI::get_entry( $entry_id );

			/** @var GF_Field_Total $total_field */
			foreach ( $total_fields as $total_field ) {
				$entry["{$total_field->id}"] = GFCommon::get_order_total( $original_form, $entry );
			}

			$return_entry = GFAPI::update_entry( $entry );

			if( is_wp_error( $return_entry ) ) {
				do_action( 'gravityview_log_error', __METHOD__ . ': Updating the entry total fields failed', $return_entry );
			} else {
				do_action( 'gravityview_log_debug', __METHOD__ . ': Updating the entry total fields succeeded' );
			}
		}
	}
}

new GravityView_Field_Total;
