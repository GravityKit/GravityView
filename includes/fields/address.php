<?php

/**
 * Add custom options for address fields
 */
class GravityView_Field_Address extends GravityView_Field {

	var $name = 'address';

	function field_options( $field_options, $template_id = '', $field_id = '', $context = '', $input_type = '' ) {

		// If this is NOT the full address field, return default options.
		if( floor( $field_id ) !== floatval( $field_id ) ) {
			return $field_options;
		}

		if( 'edit' === $context ) {
			return $field_options;
		}

		$add_options = array();

		$add_options['show_map_link'] = array(
			'type' => 'checkbox',
			'label' => __( 'Show Map Link:', 'gravityview' ),
			'desc' => __('Display a "Map It" link below the address', 'gravityview'),
			'value' => true,
			'merge_tags' => false,
		);

		return $add_options + $field_options;
	}

}

new GravityView_Field_Address;
