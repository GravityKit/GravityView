<?php
/**
 * @file class-gravityview-field-password.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Password extends GravityView_Field {

	var $name = 'password';

	var $is_searchable = false;

	/** @see GF_Field_Password */
	var $_gf_field_class_name = 'GF_Field_Password';

	var $group = 'advanced';

	public function __construct() {
		$this->label = esc_html__( 'Password', 'gravityview' );

		$this->add_hooks();

		parent::__construct();
	}

	/**
	 * Add filters to modify the front-end label and the Add Field label
	 *
	 * @since 1.17
	 *
	 * @return void
	 */
	function add_hooks() {
		add_filter( 'gravityview/common/get_form_fields', array( $this, 'add_form_fields' ), 10, 3 );

		add_filter( 'gravityview/template/field_label', array( $this, 'field_label' ), 10, 4 );
	}

	/**
	 * Use the GV Admin Field label for the Password field instead of the per-input setting
	 *
	 * @since 1.17
	 *
	 * @param string $label Field label HTML
	 * @param array $field GravityView field array
	 * @param array $form Gravity Forms form array
	 * @param array $entry Gravity Forms entry array
	 *
	 * @return string If a custom field label isn't set, return the field label for the password field
	 */
	function field_label( $label = '', $field = array(), $form = array(), $entry = array() ){

		// If using a custom label, no need to fetch the parent label
		if( ! is_numeric( $field['id'] ) || ! empty( $field['custom_label'] ) ) {
			return $label;
		}

		$field_object = GFFormsModel::get_field( $form, $field['id'] );

		if( $field_object && 'password' === $field_object->type ) {
			$label = $field['label'];
		}

		return $label;
	}

	/**
	 * If a form has list fields, add the columns to the field picker
	 *
	 * @since 1.17
	 *
	 * @param array $fields Associative array of fields, with keys as field type
	 * @param array $form GF Form array
	 * @param bool $include_parent_field Whether to include the parent field when getting a field with inputs
	 *
	 * @return array $fields with list field columns added, if exist. Unmodified if form has no list fields.
	 */
	function add_form_fields( $fields = array(), $form = array(), $include_parent_field = true ) {

		foreach ( $fields as $key => $field ) {
			if( 'password' === $field['type'] ) {

				// The Enter Password input
				if( floor( $key ) === floatval( $key ) ) {

					if( ! empty( $field['parent'] ) ) {
						$field['label']      = $field['parent']->label;
						$field['adminOnly']  = $field['parent']->adminOnly;
						$field['adminLabel'] = $field['parent']->adminLabel;
						// Don't show as a child input
						unset( $field['parent'] );
					}

					$fields[ $key ] = $field;
				} else {
					// The Confirm Password input
					unset( $fields[ $key ] );
				}
			}
		}

		return $fields;
	}

}

new GravityView_Field_Password;
