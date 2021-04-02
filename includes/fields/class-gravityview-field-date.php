<?php
/**
 * @file class-gravityview-field-date.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Add custom options for date fields
 */
class GravityView_Field_Date extends GravityView_Field {

	var $name = 'date';

	var $_gf_field_class_name = 'GF_Field_Date';

	var $is_searchable = true;

	var $search_operators = array( 'less_than', 'greater_than', 'is', 'isnot' );

	var $group = 'advanced';

	var $icon = 'dashicons-calendar-alt';

	public function __construct() {

		$this->label = esc_html__( 'Date', 'gravityview' );

		add_filter( 'gravityview/merge_tags/modifiers/value', array( $this, 'apply_format_date_modifiers' ), 10, 6 );

		parent::__construct();
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		if( 'edit' === $context ) {
			return $field_options;
		}

		$this->add_field_support('date_display', $field_options );

		return $field_options;
	}

	/**
	 * Allow Date fields to take advantage of the GV date modifiers
	 *
	 * @since 2.0
	 * @uses  GravityView_Merge_Tags::format_date
	 *
	 * @param string $return The current merge tag value to be filtered.
	 * @param string $raw_value The raw value submitted for this field. May be CSV or JSON-encoded.
	 * @param string $value The original merge tag value, passed from Gravity Forms
	 * @param string $merge_tag If the merge tag being executed is an individual field merge tag (i.e. {Name:3}), this variable will contain the field's ID. If not, this variable will contain the name of the merge tag (i.e. all_fields).
	 * @param string $modifier The string containing any modifiers for this merge tag. For example, "maxwords:10" would be the modifiers for the following merge tag: `{Text:2:maxwords:10}`.
	 * @param GF_Field $field The current field.
	 *
	 * @return string If Date field, run it through GravityView_Merge_Tags::format_date; otherwise, return the original value
	 */
	public function apply_format_date_modifiers( $return, $raw_value = '', $value = '', $merge_tag = '', $modifier = '', $field = null ) {

		if ( 'date' === $field->type ) {
			$return = GravityView_Merge_Tags::format_date( $raw_value, $modifier );
		}

		return $return;
	}

	/**
	 * Get the default date format for a field based on the field ID and the time format setting
	 *
	 * @since 1.16.4

	 * @param string $date_format The Gravity Forms date format for the field. Default: "mdy"
	 * @param int $field_id The ID of the field. Used to figure out full date/day/month/year
	 *
	 * @return string PHP date format for the date
	 */
	static public function date_display( $value = '', $date_format = 'mdy', $field_id = 0 ) {

		// Let Gravity Forms figure out, based on the date format, what day/month/year values are.
		$parsed_date = GFCommon::parse_date( $value, $date_format );

		// Are we displaying an input or the whole field?
		$field_input_id = gravityview_get_input_id_from_id( $field_id );

		$date_field_output = '';
		switch( $field_input_id ) {
			case 1:
				$date_field_output = \GV\Utils::get( $parsed_date, 'month' );
				break;
			case 2:
				$date_field_output = \GV\Utils::get( $parsed_date, 'day' );
				break;
			case 3:
				$date_field_output = \GV\Utils::get( $parsed_date, 'year' );
				break;
		}

		/**
		 * @filter `gravityview_date_format` Whether to override the Gravity Forms date format with a PHP date format
		 * @see https://codex.wordpress.org/Formatting_Date_and_Time
		 * @param null|string Date Format (default: $field->dateFormat)
		 */
		$full_date_format = apply_filters( 'gravityview_date_format', $date_format );

		$full_date = GFCommon::date_display( $value, $full_date_format );

		// If the field output is empty, use the full date.
		// Note: The output might be empty because $parsed_date didn't parse correctly.
		return ( '' === $date_field_output ) ? $full_date : $date_field_output;
	}

}

new GravityView_Field_Date;
