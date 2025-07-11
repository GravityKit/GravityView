<?php

namespace GV;

/**
 * Represents a collection that has a position (fields, widgets and search fields).
 *
 * @since $ver$
 */
interface Collection_Position_Aware {
	/**
	 * Get a copy of this \GV\Field_Collection filtered by position.
	 *
	 * @since $ver$
	 *
	 * @param string $position The position to get the fields for.
	 *                         Can be a wildcard *
	 *
	 * @return static|Collection A filtered collection, filtered by position.
	 */
	public function by_position( $position );
}
