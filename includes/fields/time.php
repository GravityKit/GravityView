<?php

/**
 * Add custom options for date fields
 */
class GravityView_Field_Time extends GravityView_Field {

	var $name = 'time';

	function field_options( $field_options, $template_id = '', $field_id = '', $context = '', $input_type = '' ) {

		// Set variables
		parent::field_options( $field_options, $template_id, $field_id, $context, $input_type );

		if( 'edit' === $context ) {
			return $field_options;
		}

		add_filter('gravityview_date_format', array( $this, '_filter_date_display_date_format' ) );
		$this->add_field_support('date_display', $field_options );
		remove_filter('gravityview_date_format', array( $this, '_filter_date_display_date_format' ) );

		return $field_options;
	}

	/**
	 * Return the field's time format by fetching the form ID and checking the field settings
	 * @since 1.14
	 * @return string Either "12" or "24". "12" is default.
	 */
	private function _get_time_format() {
		global $post;

		// GF defaults to 12, so should we.
		$time_format = '12';

		$current_form = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : gravityview_get_form_id( $post->ID );

		if( $current_form ) {
			$form = GFAPI::get_form( $current_form );
			if( $form ) {
				$field = GFFormsModel::get_field( $form, floor( $this->_field_id ) );
				if( $field ) {
					$field->sanitize_settings(); // Make sure time is set
					$time_format = $field->timeFormat;
				}
			}

		}
		return $time_format;
	}

	/**
	 * Modify the default PHP date formats used by the time field based on the field IDs and the field settings
	 * @since 1.14
	 * @return string
	 */
	public function _filter_date_display_date_format() {

		$time_format = $this->_get_time_format();
		$field_id = $this->_field_id;

		return self::date_format( $time_format, $field_id );
	}

	/**
	 * Get the default date format for a field based on the field ID and the time format setting
	 *
	 * @param string $time_format The time format ("12" or "24"). Default: "12" {@since 1.14}
	 * @param int $field_id The ID of the field. Used to figure out full time/hours/minutes/am/pm {@since 1.14}
	 *
	 * @return string PHP date format for the time
	 */
	static public function date_format( $time_format = '12', $field_id = 0 ) {

		$field_id_array = explode( '.', $field_id );

		$field_input_id = isset( $field_id_array[1] ) ? intval( $field_id_array[1] ) : 0;

		// This doesn't take into account 24-hour
		switch( $field_input_id ) {
			// Hours
			case 1:
				return ( $time_format === '12' ) ? 'h' : 'H';
				break;
			// Minutes
			case 2:
				return 'i';
				break;
			// AM/PM
			case 3:
				return 'A';
				break;
			// Full time field
			case 0:
				return ( $time_format === '12' ) ? 'h:i A' : 'H:i';
				break;
		}
	}

}

new GravityView_Field_Time;
