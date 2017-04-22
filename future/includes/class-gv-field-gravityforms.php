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
	 * @var \GF_Field The backing Gravity Forms field.
	 */
	public $field;

	/**
	 * Create self from a configuration array.
	 *
	 * @param array $configuration The configuration array.
	 * @see \GV\Field::as_configuration()
	 * @internal
	 * @since future
	 *
	 * @return \GV\GF_Field|null The field implementation or null on error.
	 */
	public static function from_configuration( $configuration ) {
		if ( empty( $configuration['id'] ) || ! is_numeric( $configuration['id'] ) ) {
			gravityview()->log->error( 'Invalid configuration[id] supplied.' );
			return null;
		}

		if ( empty( $configuration['form_id'] ) || ! $form = \GV\GF_Form::by_id( $configuration['form_id'] )  ) {
			gravityview()->log->error( 'Invalid configuration[form_id] supplied.' );
			return null;
		}

		$field = self::by_id( $form, $configuration['id'] );
		$field->update_configuration( $configuration );
		return $field;
	}

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

	/**
	 * A proxy getter for the backing Gravity View field.
	 *
	 * @param string $key The property to get.
	 *
	 * @return mixed The value of the Gravity View field property, or null if not exists.
	 */
	public function __get( $key ) {
		if ( $this->field ) {
			return $this->field->$key;
		}
	}
}
