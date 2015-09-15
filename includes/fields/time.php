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

		add_filter('gravityview_date_format', array( $this, 'date_format' ) );
		$this->add_field_support('date_display', $field_options );
		remove_filter('gravityview_date_format', array( $this, 'date_format' ) );

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
	 * Get the default date format for a field based on the field ID and the time format setting
	 * @return string PHP date format for the time
	 */
	function date_format() {

		$time_format = $this->_get_time_format();

		$field_id_array = explode( '.', $this->_field_id );

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
				return ( $time_format === '12' ) ? 'h:iA' : 'H:i';
				break;
		}
	}

}

new GravityView_Field_Time;
