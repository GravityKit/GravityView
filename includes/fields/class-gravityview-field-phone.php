<?php
/**
 * @file class-gravityview-field-phone.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Phone extends GravityView_Field {

	var $name = 'phone';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot', 'contains', 'starts_with', 'ends_with' );

	/** @see GF_Field_Phone */
	var $_gf_field_class_name = 'GF_Field_Phone';

	var $group = 'advanced';

	public function __construct() {
		$this->label = esc_html__( 'Phone', 'gravityview' );
		parent::__construct();
	}

	/**
	 * Add option to link phone number
	 *
	 * @since 1.17
	 *
	 * @param array $field_options
	 * @param string $template_id
	 * @param string $field_id
	 * @param string $context
	 * @param string $input_type
	 *
	 * @return array
	 */
	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		if( 'edit' === $context ) {
			return $field_options;
		}

		$field_options['link_phone'] = array(
	        'type' => 'checkbox',
	        'label' => __( 'Make Phone Number Clickable', 'gravityview' ),
	        'desc' => __( 'Allow dialing a number by clicking it?', 'gravityview'),
	        'value' => true,
        );

		return $field_options;
	}
}

new GravityView_Field_Phone;
