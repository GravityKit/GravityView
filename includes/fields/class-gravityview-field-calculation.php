<?php
/**
 * @file class-gravityview-field-calculation.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Calculation extends GravityView_Field {

	var $name = 'calculation';

	var $is_searchable = false;

	var $group = 'pricing';

	var $_gf_field_class_name = 'GF_Field_Calculation';

	/**
	 * GravityView_Field_Calculation constructor.
	 */
	public function __construct() {

		$this->label = esc_html__( 'Calculation', 'gk-gravityview' );

		add_filter( 'gravityview_blocklist_field_types', array( $this, 'blocklist_field_types' ), 10, 2 );

		parent::__construct();
	}

	/**
	 * @depecated 2.14
	 */
	public function blacklist_field_types( $field_types = array(), $context = '' ) {
		_deprecated_function( __METHOD__, '2.14', 'GravityView_Field_Calculation::blocklist_field_types' );
		return $this->blocklist_field_types( $field_types, $context );
	}


	/**
	 * Don't show the Calculation field in field picker
	 *
	 * @since 2.14
	 *
	 * @param array  $field_types Array of field types
	 * @param string $context
	 *
	 * @return array Field types with calculation added, if not Edit Entry context
	 */
	public function blocklist_field_types( $field_types = array(), $context = '' ) {

		// Allow Calculation field in Edit Entry
		if ( 'edit' !== $context ) {
			$field_types[] = $this->name;
		}

		return $field_types;
	}
}

new GravityView_Field_Calculation();
