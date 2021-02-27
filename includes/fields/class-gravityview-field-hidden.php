<?php
/**
 * @file class-gravityview-field-hidden.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Hidden extends GravityView_Field {

	var $name = 'hidden';

	var $is_searchable = true;

	var $search_operators = array( 'contains', 'is', 'isnot', 'starts_with', 'ends_with' );

	var $_gf_field_class_name = 'GF_Field_Hidden';

	var $group = 'standard';

	var $icon = 'dashicons-hidden';

	public function __construct() {
		$this->label = esc_html__( 'Hidden', 'gravityview' );

		$this->edit_entry_add_hooks();

		parent::__construct();
	}

	/**
	 * Add Edit Entry hooks
	 *
	 * @since 1.17
	 *
	 * @return void
	 */
	private function edit_entry_add_hooks() {
		add_filter( 'gravityview/edit_entry/form_fields', array( $this, 'edit_entry_fix_hidden_fields' ) );
	}

	/**
	 * Convert Hidden fields to be Text fields in Edit Entry
	 *
	 * @since 1.9.2
	 * @since 1.17 Moved to GravityView_Field_Hidden class
	 *
	 * @param GF_Field[] $fields Array of fields to be shown on the Edit Entry screen
	 *
	 * @return GF_Field[] Array of fields, with any hidden fields replaced with text fields
	 */
	function edit_entry_fix_hidden_fields( $fields ) {

		/** @type GF_Field $field */
		foreach( $fields as &$field ) {

			if ( 'hidden' === $field->type ) {

				/**
				 * @filter `gravityview/edit_entry/reveal_hidden_field` Convert Hidden fields into Text fields on Edit Entry
				 * @since 1.22.6
				 * @since 2.7 Changed default value to `false` from `true`
				 * @param bool $reveal_hidden_field True: Convert the hidden field to text; False: Leave hidden
				 * @param GF_Field_Hidden $field The field in question
				 */
				$reveal_hidden_field = apply_filters( 'gravityview/edit_entry/reveal_hidden_field', false, $field );

				if( ! $reveal_hidden_field ) {
					continue;
				}

				// Replace GF_Field_Hidden with GF_Field_Text, copying all the data from $field
				$field = new GF_Field_Text( $field );

				// Everything is copied from $field, so we need to manually set the type
				$field->type = 'text';
			}
		}

		return $fields;
	}

}

new GravityView_Field_Hidden;
