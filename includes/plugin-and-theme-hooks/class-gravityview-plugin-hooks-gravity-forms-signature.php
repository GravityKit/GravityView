<?php
/**
 * Customization for the Gravity Forms Signature Addon
 *
 * @file      class-gravityview-plugin-hooks-gravity-forms-signature.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2016, Katz Web Services, Inc.
 *
 * @since 1.17
 */

use GV\Utils;

/**
 * @inheritDoc
 * @since 1.17
 */
class GravityView_Plugin_Hooks_Gravity_Forms_Signature extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @type string Class that should be exist in a plugin or theme. Used to check whether plugin is active.
	 * @since 1.17
	 */
	protected $class_name = 'GFSignature';

	protected function add_hooks() {

		// Remove $_POST values so GF doesn't change output HTML laout
		add_action( 'gravityview/edit_entry/after_update', array( $this, 'after_edit_entry' ), 10, 2 );

		// Set the priority to 5 so it processes before the Signature field input is generated
		// This way, we can modify the $_POST value before the Signature Addon uses it.
		add_filter( 'gform_field_input', array( $this, 'edit_entry_field_input' ), 5, 5 );
	}

	/**
	 * We need to remove the $value used by Gravity Forms so it instead checks for the $_POST field values
	 *
	 * In ~line 541, this code would be used if we didn't override in this method:
	 *
	 * `if (RG_CURRENT_VIEW == "entry" && $value){`
	 *
	 * We don't want that code (with the download/delete icons). So unsetting the $_POST here forces using the "sign again" code instead.
	 *
	 * @see GFSignature::signature_input
	 *
	 * @param array $form GF form array
	 * @param int   $entry_id Entry ID being edited
	 */
	function after_edit_entry( $form, $entry_id ) {

		$signature_fields = GFAPI::get_fields_by_type( $form, 'signature' );

		foreach ( $signature_fields as $field ) {
			unset( $_POST[ "input_{$field->id}" ] );
		}
	}

	/**
	 * The Signature Addon only displays the output in the editable form if it thinks it's in the Admin or a form has been submitted
	 *
	 * @since 1.17
	 *
	 * @param string       $field_content Always empty. Returning not-empty overrides the input.
	 * @param GF_Field     $field
	 * @param string|array $value If array, it's a field with multiple inputs. If string, single input.
	 * @param int          $lead_id Lead ID. Always 0 for the `gform_field_input` filter.
	 * @param int          $form_id Form ID
	 *
	 * @return string Empty string forces Gravity Forms to use the $_POST values
	 */
	function edit_entry_field_input( $field_content = '', $field = null, $value = '', $lead_id = 0, $form_id = 0 ) {

		$context = function_exists( 'gravityview_get_context' ) ? gravityview_get_context() : '';

		if ( 'signature' !== $field->type || 'edit' !== $context ) {
			return $field_content;
		}

		// We need to fetch a fresh version of the entry, since the saved entry hasn't refreshed in GV yet.
		$entry = GravityView_View::getInstance()->getCurrentEntry();

		if ( ! is_array( $entry ) || empty( $entry['id'] ) ) {
			return $field_content;
		}

		$entry = GFAPI::get_entry( $entry['id'] );

		if ( is_wp_error( $entry ) ) {
			return $field_content;
		}

		$entry_value = Utils::get( $entry, $field->id );

		$_POST[ "input_{$field->id}" ]                               = $entry_value; // Used when Edit Entry form *is* submitted
		$_POST[ "input_{$form_id}_{$field->id}_signature_filename" ] = $entry_value; // Used when Edit Entry form *is not* submitted

		return ''; // Return empty string to force using $_POST values instead
	}
}

new GravityView_Plugin_Hooks_Gravity_Forms_Signature();
