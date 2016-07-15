<?php
/**
 * @file class-gravityview-field-payment-amount.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 1.16
 */

class GravityView_Field_Payment_Amount extends GravityView_Field {

	var $name = 'payment_amount';

	var $is_searchable = true;

	var $is_numeric = true;

	var $search_operators = array( 'is', 'isnot', 'greater_than', 'less_than', 'contains' );

	var $group = 'pricing';

	var $_custom_merge_tag = 'payment_amount';

	/**
	 * GravityView_Field_Payment_Amount constructor.
	 */
	public function __construct() {
		$this->label = esc_html__( 'Payment Amount', 'gravityview' );

		add_filter( 'gravityview_field_entry_value_' . $this->name . '_pre_link', array( $this, 'get_content' ), 10, 4 );

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
	 * @param  array $field_settings Settings for the particular GV field
	 * @param array $field Current field being displayed
	 *
	 * @return String values for this field based on the numeric values used by Gravity Forms
	 */
	public function get_content( $output = '', $entry = array(), $field_settings = array(), $field = array() ) {

		/** Overridden by a template. */
		if( ! empty( $field['field_path'] ) ) { return $output; }

		$amount = rgar( $entry, 'payment_amount' );
		$return = GFCommon::to_money( $amount, rgar( $entry, 'currency' ) );

		return $return;
	}

	/**
	 * Add {payment_amount} merge tag
	 *
	 * @since 1.16
	 **
	 * @param array $matches Array of Merge Tag matches found in text by preg_match_all
	 * @param string $text Text to replace
	 * @param array $form Gravity Forms form array
	 * @param array $entry Entry array
	 * @param bool $url_encode Whether to URL-encode output
	 *
	 * @return string Original text if {date_created} isn't found. Otherwise, replaced text.
	 */
	public function replace_merge_tag( $matches = array(), $text = '', $form = array(), $entry = array(), $url_encode = false, $esc_html = false  ) {

		$return = $text;

		foreach ( $matches as $match ) {

			$full_tag = $match[0];
			$modifier = isset( $match[1] ) ? $match[1] : false;

			$amount = rgar( $entry, 'payment_amount' );

			$formatted_amount = ( 'raw' === $modifier ) ? $amount : GFCommon::to_money( $amount, rgar( $entry, 'currency' ) );

			$return = str_replace( $full_tag, $formatted_amount, $return );
		}

		unset( $formatted_amount, $amount, $full_tag, $matches );

		return $return;
	}
}

new GravityView_Field_Payment_Amount;
