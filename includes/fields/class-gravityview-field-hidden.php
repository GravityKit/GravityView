<?php
/**
 * @file class-gravityview-field-hidden.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Hidden extends GravityView_Field {

	var $name = 'hidden';

	var $is_searchable = false;

	var $_gf_field_class_name = 'GF_Field_Hidden';

	var $group = 'standard';

	public function __construct() {
		$this->label = esc_html__( 'Hidden', 'gravityview' );

		add_filter( 'gravityview/edit_entry/prepare_form_field/hidden', array( $this, 'transform_for_edit' ) );

		parent::__construct();
	}

	function transform_for_edit( $field ) {
		$text_field = new GF_Field_Text( $field );
		$text_field->type = 'text';
		return $text_field;
	}

}

new GravityView_Field_Hidden;
