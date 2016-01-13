<?php
/**
 * @file class-gravityview-field-date-created.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Date_Created extends GravityView_Field {

	var $name = 'date_created';

	var $search_operators = array( 'less_than', 'greater_than', 'is', 'isnot' );

	var $group = 'meta';

	var $contexts = array( 'single', 'multiple', 'export' );

	var $_custom_merge_tag = 'date_created';

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

	/**
	 * Add {date_created} merge tag and format the values using format_date
	 *
	 * @since 1.16
	 *
	 * @see http://docs.gravityview.co/article/281-the-createdby-merge-tag for usage information
	 *
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

		/** Use $this->name instead of date_created because Payment Date uses this as well*/
		$date_created = rgar( $entry, $this->name );

		foreach ( $matches as $match ) {

			$full_tag          = $match[0];
			$property          = $match[1];

			$formatted_date = GravityView_Merge_Tags::format_date( $date_created, $property );

			$return = str_replace( $full_tag, $formatted_date, $return );
		}

		return $return;
	}

}

new GravityView_Field_Date_Created;