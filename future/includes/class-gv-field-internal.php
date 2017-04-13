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
}
