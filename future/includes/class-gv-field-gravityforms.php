<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The Gravity Forms \GF_Field field object wrapper.
 */
class GF_Field extends Field {
	/**
	 * Get a \GV\GF_Field by \GV\GF_Form and Field ID.
	 *
	 * @param \GV\GF_Form $form The Gravity Form form.
	 * @param int $field_id The Gravity Form field ID for the $form.
	 *
	 * @return \GV\Field|null The requested field or null if not found.
	 */
	public static function by_id( $form, $field_id ) {

		if ( ! $form || ! is_object( $form ) || ! is_a( $form, '\GV\GF_Form' ) ) {
			gravityview()->log->error( '$form is not a \GV\GF_Form instance' );
			return null;
		}

		if ( empty( $form->form ) ) {
			gravityview()->log->error( '$form is not initialized with a backing Gravity Forms form' );
			return null;
		}


		$gv_field = \GFFormsModel::get_field( $form->form, $field_id );

		if ( ! $gv_field ) {
			gravityview()->log->error( 'Invalid $field_id #{field_id} for current source' );
			return null;
		}

		$field = new self();
		$field->ID = $field_id;
		$field->field = $gv_field;

		return $field;
	}

	/**
	 * Retrieve the value for this field.
	 *
	 * @param \GV\Field_Value_Context $context Provides some context on where to get the value for this field from.
	 *  Requires the \GV\Entry in the context.
	 *
	 * @return mixed The value for this field.
	 */
	public function get_value( \GV\Field_Value_Context $context ) {
		$entry = $context->entry;

		if ( ! $entry || ! is_object( $entry ) || ! is_a( $entry, '\GV\Entry' ) ) {
			gravityview()->log->error( '$entry is not a valid \GV\GF_Entry instance' );
			return null;
		}

		$value = \RGFormsModel::get_lead_field_value( $context->entry->as_entry(), $this->field );
		
		/** Apply parent filters. */
		return $this->get_value_filters( $value, $context );
	}
}
