<?php
/**
 * @file class-gravityview-field-transaction-type.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 1.16
 */

class GravityView_Field_Transaction_Type extends GravityView_Field {

	var $name = 'transaction_type';

	var $is_searchable = true;

	var $is_numeric = true;

	var $search_operators = array( 'is', 'isnot', 'in', 'not in' );

	var $group = 'pricing';

	var $_custom_merge_tag = 'transaction_type';

	var $icon = 'dashicons-cart';

	/**
	 * @var int One-time payments are stored by Gravity Forms in the database as `1`
	 */
	const ONE_TIME_PAYMENT = 1;

	/**
	 * @var int Subscriptions are stored by Gravity Forms in the database as `2`
	 */
	const SUBSCRIPTION = 2;

	/**
	 * GravityView_Field_Transaction_Type constructor.
	 */
	public function __construct() {
		$this->label       = esc_html__( 'Transaction Type', 'gk-gravityview' );
		$this->description = esc_html__( 'The type of the order: one-time payment or subscription', 'gk-gravityview' );

		add_filter( 'gravityview_field_entry_value_' . $this->name . '_pre_link', array( $this, 'get_content' ), 10, 4 );
		add_filter( 'gravityview/field/transaction_type/value', array( $this, 'get_value' ), 10 );

		parent::__construct();
	}

	/**
	 * Filter the value of the field
	 *
	 * @todo Consider how to add to parent class
	 *
	 * @since 1.16
	 *
	 * @param string $output HTML value output
	 * @param array  $entry The GF entry array
	 * @param  array  $field_settings Settings for the particular GV field
	 * @param array  $field Current field being displayed
	 *
	 * @return String values for this field based on the numeric values used by Gravity Forms
	 */
	public function get_content( $output, $entry = array(), $field_settings = array(), $field = array() ) {

		/** Overridden by a template. */
		if ( ! empty( $field['field_path'] ) ) {
			return $output; }

		return $this->get_string_from_value( $output );
	}

	/**
	 * Filter the value of the field (future)
	 *
	 * @since 2.0
	 *
	 * @param mixed $value The value in.
	 *
	 * @return mixed The value out.
	 */
	public function get_value( $value ) {
		return $this->get_string_from_value( $value );
	}

	/**
	 * Get the string output based on the numeric value used by Gravity Forms
	 *
	 * @since 1.16
	 *
	 * @param int|string $value Number value for the field
	 *
	 * @return string Based on $value; `1`: "One-Time Payment"; `2`: "Subscription"
	 */
	private function get_string_from_value( $value ) {

		switch ( intval( $value ) ) {
			case self::ONE_TIME_PAYMENT:
			default:
				$return = __( 'One-Time Payment', 'gk-gravityview' );
				break;

			case self::SUBSCRIPTION:
				$return = __( 'Subscription', 'gk-gravityview' );
				break;
		}

		return $return;
	}
}

new GravityView_Field_Transaction_Type();
