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

	var $icon = 'dashicons-cart';

	var $_custom_merge_tag = 'payment_amount';

	/**
	 * GravityView_Field_Payment_Amount constructor.
	 */
	public function __construct() {
		$this->label = esc_html__( 'Payment Amount', 'gk-gravityview' );

		add_filter( 'gravityview_field_entry_value_' . $this->name . '_pre_link', array( $this, 'get_content' ), 10, 4 );
		add_filter( 'gravityview/field/payment_amount/value', array( $this, 'get_value' ), 10, 6 );

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
	public function get_content( $output = '', $entry = array(), $field_settings = array(), $field = array() ) {

		/** Overridden by a template. */
		if ( ! empty( $field['field_path'] ) ) {
			return $output; }

		$amount = \GV\Utils::get( $entry, 'payment_amount' );
		$return = GFCommon::to_money( $amount, \GV\Utils::get( $entry, 'currency' ) );

		return $return;
	}

	/**
	 * Filter the value of the field, future.
	 *
	 * @since 2.0
	 *
	 * @param mixed       $value  The value of the field.
	 * @param \GV\Field   $field  The field as seen by future.
	 * @param \GV\View    $view   The view requested in.
	 * @param \GV\Source  $source The data source (form).
	 * @param \GV\Entry   $entry  The entry.
	 * @param \GV\Request $request The request context.
	 *
	 * @return mixed $value The filtered value.
	 */
	public function get_value( $value, $field, $view, $source, $entry, $request ) {
		return $this->get_content( $value, $entry->as_entry(), $field->as_configuration() );
	}

	/**
	 * Add {payment_amount} merge tag
	 *
	 * @since 1.16
	 * *
	 * @param array  $matches Array of Merge Tag matches found in text by preg_match_all
	 * @param string $text Text to replace
	 * @param array  $form Gravity Forms form array
	 * @param array  $entry Entry array
	 * @param bool   $url_encode Whether to URL-encode output
	 *
	 * @return string Original text if {date_created} isn't found. Otherwise, replaced text.
	 */
	public function replace_merge_tag( $matches = array(), $text = '', $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		$return = $text;

		foreach ( $matches as $match ) {

			$full_tag = $match[0];
			$modifier = isset( $match[1] ) ? $match[1] : false;

			$amount = \GV\Utils::get( $entry, 'payment_amount' );

			$formatted_amount = ( 'raw' === $modifier ) ? $amount : GFCommon::to_money( $amount, \GV\Utils::get( $entry, 'currency' ) );

			$return = str_replace( $full_tag, $formatted_amount, $return );
		}

		unset( $formatted_amount, $amount, $full_tag, $matches );

		return $return;
	}
}

new GravityView_Field_Payment_Amount();
