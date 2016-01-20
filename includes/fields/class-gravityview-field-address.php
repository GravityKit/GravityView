<?php
/**
 * @file class-gravityview-field-address.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Add custom options for address fields
 */
class GravityView_Field_Address extends GravityView_Field {

	var $name = 'address';

	var $group = 'advanced';

	var $_gf_field_class_name = 'GF_Field_Address';

	public function __construct() {
		$this->label = esc_html__( 'Address', 'gravityview' );
		parent::__construct();
	}

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
