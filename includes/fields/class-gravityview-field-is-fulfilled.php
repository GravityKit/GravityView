<?php
/**
 * @file class-gravityview-field-is-fulfilled.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 1.16
 */

class GravityView_Field_Is_Fulfilled extends GravityView_Field {

	var $name = 'is_fulfilled';

	var $is_searchable = true;

	var $is_numeric = false;

	var $search_operators = array( 'is', 'isnot' );

	var $group = 'pricing';

	var $_custom_merge_tag = 'is_fulfilled';

	/**
	 * @var int The value used by Gravity Forms when the order has not been fulfilled
	 */
	const NOT_FULFILLED = 0;

	/**
	 * @var int The value used by Gravity Forms when the order has been fulfilled
	 */
	const FULFILLED = 1;

	/**
	 * GravityView_Field_Is_Fulfilled constructor.
	 */
	public function __construct() {
		$this->label = esc_html__( 'Is Fulfilled', 'gravityview' );
		$this->default_search_label = $this->label;

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
	public function get_content( $output, $entry = array(), $field_settings = array(), $field = array() ) {

		/** Overridden by a template. */
		if( ! empty( $field['field_path'] ) ) { return $output; }

		return $this->get_string_from_value( $output );
	}

	/**
	 * Get the string output based on the numeric value used by Gravity Forms
	 *
	 * @since 1.16
	 *
	 * @param int|string $value Number value for the field
	 *
	 * @return string
	 */
	private function get_string_from_value( $value ) {

		switch ( intval( $value ) ) {
			case self::NOT_FULFILLED:
			default:
				$return = __('Not Fulfilled', 'gravityview');
				break;

			case self::FULFILLED:
				$return = __('Fulfilled', 'gravityview');
				break;
		}

		return $return;
	}

	/**
	 * Add {is_fulfilled} merge tag
	 *
	 * @since 1.16
	 **
	 * @param array $matches Array of Merge Tag matches found in text by preg_match_all
	 * @param string $text Text to replace
	 * @param array $form Gravity Forms form array
	 * @param array $entry Entry array
	 * @param bool $url_encode Whether to URL-encode output
	 *
	 * @return string Original text if {is_fulfilled} isn't found. Otherwise, "Not Fulfilled" or "Fulfilled"
	 */
	public function replace_merge_tag( $matches = array(), $text = '', $form = array(), $entry = array(), $url_encode = false, $esc_html = false  ) {

		$return = $text;

		foreach ( $matches as $match ) {

			$full_tag = $match[0];

			$fulfilled = rgar( $entry, 'is_fulfilled' );

			$value = $this->get_string_from_value( $fulfilled );

			$return = str_replace( $full_tag, $value, $return );
		}

		unset( $formatted_amount, $value, $amount, $full_tag, $matches );

		return $return;
	}
}

new GravityView_Field_Is_Fulfilled;
