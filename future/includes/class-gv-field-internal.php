<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The internal \GF_Field field object wrapper.
 *
 * Used for custom content fields, etc.
 */
class Internal_Field extends Field {
	/**
	 * Get a \GV\GF_Field from an internal Gravity View ID.
	 *
	 * @param int $field_id The internal Gravity View ID.
	 *
	 * @return \GV\Field|null The requested field or null if not found.
	 */
	public static function by_id( $field_id ) {
		$field = new self();
		$field->ID = $field_id;

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
	public function get_value( Field_Value_Context $context ) {
		$entry = $context->entry;

		if ( ! $entry || ! is_object( $entry ) || ! is_a( $entry, '\GV\Entry' ) ) {
			gravityview()->log->error( '$entry is not a valid \GV\GF_Entry instance' );
			return null;
		}

		/**
		 * @todo Implement in subclasses, once available.
		 *
		 * For example the "content" field will be empty here. It's
		 *  value is actually currently retrieved inside ...
		 *
		 * *drumroll*
		 *
		 * A TEMPLATE :)
		 */
		$value = \rgar( $entry->as_entry(), $this->ID );
		
		/** Apply parent filters. */
		return $this->get_value_filters( $value, $context );
	}
}
