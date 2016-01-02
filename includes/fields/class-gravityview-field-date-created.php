<?php

class GravityView_Field_Date_Created extends GravityView_Field {

	var $name = 'date_created';

	var $search_operators = array( 'less_than', 'greater_than', 'is', 'isnot' );

	var $group = 'meta';

	var $contexts = array( 'single', 'multiple', 'export' );

	/**
	 * GravityView_Field_Date_Created constructor.
	 */
	public function __construct() {
		$this->label = esc_attr__( 'Date Created', 'gravityview' );
		$this->description = esc_attr__( 'The date the entry was created.', 'gravityview' );
		parent::__construct();
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		if( 'edit' === $context ) {
			return $field_options;
		}

		$this->add_field_support('date_display', $field_options );

		return $field_options;
	}

	public static function format( $value, $format = '', $context = 'display' ) {

		/**
		 * @filter `gravityview_date_created_adjust_timezone` Whether to adjust the timezone for entries. \n
		 * date_created is stored in UTC format. Convert search date into UTC (also used on templates/fields/date_created.php)
		 * @since 1.16
		 * @param[out,in] boolean $adjust_tz  Use timezone-adjusted datetime? If true, adjusts date based on blog's timezone setting. If false, uses UTC setting. Default: true
		 * @param[in] string $context Where the filter is being called from. `display` in this case.
		 */
		$adjust_tz = apply_filters( 'gravityview_date_created_adjust_timezone', true, $context );

		/**
		 * date_created is stored in UTC format. Fetch in the current blog's timezone if $adjust_tz is true
		 */
		$tz_value = $adjust_tz ? get_date_from_gmt( $value ) : $value;

		if( $format ) {

			$output = date_i18n( $format, strtotime( $tz_value ) );

		} else {

			/**
			 * @filter `gravityview_date_format` Whether to override the Gravity Forms date format with a PHP date format
			 * @see https://codex.wordpress.org/Formatting_Date_and_Time
			 * @param null|string Date Format (default: $field->dateFormat)
			 */
			$format = apply_filters( 'gravityview_date_format', rgar($field, "dateFormat") );

			$output = GFCommon::date_display( $tz_value, $format );
		}

		return $output;
	}

}

new GravityView_Field_Created_By;
