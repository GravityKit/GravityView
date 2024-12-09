<?php
/**
 * @file class-gravityview-field-name.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Name extends GravityView_Field {

	var $name = 'name';

	/** @see GF_Field_Name */
	var $_gf_field_class_name = 'GF_Field_Name';

	var $group = 'advanced';

	public $search_operators = array( 'is', 'isnot', 'contains' );

	var $is_searchable = true;

	var $icon = 'dashicons-admin-users';

	public function __construct() {
		$this->label = esc_html__( 'Name', 'gk-gravityview' );

		parent::__construct();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 2.29.0
	 */
	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {
		if ( 'edit' === $context ) {
			return $field_options;
		}

		$field_options['show_as_initials'] = array(
			'type'     => 'checkbox',
			'label'    => __( 'Show as initials', 'gk-gravityview' ),
			'desc'     => __( 'This displays the first letter of the first and last names.', 'gk-gravityview' ),
			'value'    => '',
			'group'    => 'display',
		);


		return $field_options;
	}

	/**
	 * Converts a full name or string to initials.
	 *
	 * @since TBD
	 *
	 * @param string $value The full name or string to convert.
	 * 
	 * @return string The initials.
	 */
	public static function convert_to_initials( $value ) {
		$names    = explode( ' ', $value );

		$display_value = '';
	
		foreach ( $names as $name ) {
			$first_char = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1 ) : substr( $name, 0, 1 );
			$upper_char = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $first_char ) : strtoupper( $first_char );
	
			$display_value .= trim( $upper_char ) . '.';
		}

		return $display_value;
	}
}

new GravityView_Field_Name();
