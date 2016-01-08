<?php
/**
 * @file class-gravityview-field-payment-amount.php
 * @package GravityView
 * @subpackage includes\fields
 * @since TODO
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
		$this->label = esc_attr__( 'Payment Amount', 'gravityview' );
		parent::__construct();
	}

	/**
	 * Add {payment_amount} merge tag
	 *
	 * @since TODO
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

			$full_tag          = $match[0];

			$amount = rgar( $entry, 'payment_amount' );

			$formatted_amount = GFCommon::to_money( $amount, rgar( $entry, 'currency' ) );

			$return = str_replace( $full_tag, $formatted_amount, $return );
		}

		unset( $formatted_amount, $amount, $full_tag, $matches );

		return $return;
	}
}

new GravityView_Field_Payment_Amount;
