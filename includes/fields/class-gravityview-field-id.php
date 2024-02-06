<?php
/**
 * @file class-gravityview-field-id.php
 * @since 2.10
 * @subpackage includes\fields
 * @package GravityView
 */

class GravityView_Field_ID extends GravityView_Field {

	var $name = 'id';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot', 'greater_than', 'less_than', 'in', 'not_in' );

	var $group = 'meta';

	var $icon = 'dashicons-code-standards';

	var $is_numeric = true;

	public function __construct() {
		$this->label       = esc_html__( 'Entry ID', 'gk-gravityview' );
		$this->description = __( 'The unique ID of the entry.', 'gk-gravityview' );
		parent::__construct();
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		if ( 'edit' === $context ) {
			return $field_options;
		}

		if ( 'single' === $context ) {
			unset( $field_options['new_window'] );
		}

		return $field_options;
	}
}

new GravityView_Field_ID();
