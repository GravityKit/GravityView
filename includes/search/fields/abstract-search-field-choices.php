<?php

namespace GV\Search\Fields;

/**
 * Represents a search field with choices.
 *
 * @since $ver$
 * @template T The type of the value.
 * @extends Search_Field<T>
 */
abstract class Search_Field_Choices extends Search_Field {
	/**
	 * Returns the choices for the field.
	 *
	 * @since $ver$
	 *
	 * @return array{text: string, value:string} The choices.
	 */
	abstract public function get_choices(): array;
}
