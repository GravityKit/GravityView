<?php
/**
 * @file class-gravityview-field-uid.php
 * @since 1.17.4
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_UID extends GravityView_Field {

	var $name = 'uid';

	var $group = 'advanced';

	public function __construct() {
		$this->label = esc_html__( 'Unique ID', 'gravityview' );
		$this->default_search_label = $this->label;

		$this->edit_entry_add_hooks();

		parent::__construct();
	}

	/**
	 * Add Edit Entry hooks
	 *
	 * @since 1.17.4
	 *
	 * @return void
	 */
	private function edit_entry_add_hooks() {
		add_filter( 'gravityview/edit_entry/form_fields', array( $this, 'edit_entry_fix_hidden_fields' ) );
	}

	/**
	 * Convert Unique ID fields to be Text fields in Edit Entry
	 *
	 * @since 1.17.4
	 *
	 * @param GF_Field[] $fields Array of fields to be shown on the Edit Entry screen
	 *
	 * @return GF_Field[] Array of fields, with any hidden fields replaced with text fields
	 */
	function edit_entry_fix_hidden_fields( $fields ) {

		/** @var GF_Field $field */
		foreach( $fields as &$field ) {
			if ( 'uid' === $field->type ) {

				// Replace GF_Field with GF_Field_Text, copying all the data from $field
				$field = new GF_Field_Text( $field );

				// Everything is copied from $field, so we need to manually set the type
				$field->type = 'text';
			}
		}

		return $fields;
	}

}

new GravityView_Field_UID;
