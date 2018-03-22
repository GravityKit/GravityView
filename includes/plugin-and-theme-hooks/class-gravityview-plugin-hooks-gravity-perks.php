<?php
/**
 * Fix Gravity Perks conflicts with GravityView
 *
 * @file      class-gravityview-plugin-hooks-gravity-perks.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      https://gravityview.co
 * @copyright Copyright 2016, Katz Web Services, Inc.
 *
 * @since 1.17.5
 */

/**
 * @inheritDoc
 * @since 1.17.5
 */
class GravityView_Plugin_Hooks_Gravity_Perks extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @var string Check for the Gravity Perks class
	 */
	protected $class_name = 'GravityPerks';


	/**
	 * Filter the values shown in GravityView frontend
	 *
	 * @since 1.17
	 */
	protected function add_hooks() {

		parent::add_hooks();

		add_filter( 'gravityview/edit_entry/form_fields', array( $this, 'edit_entry_fix_uid_fields' ) );

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
	public function edit_entry_fix_uid_fields( $fields ) {

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

new GravityView_Plugin_Hooks_Gravity_Perks;