<?php

namespace GV\Search\Fields;

/**
 * Represents a search field with possible choices.
 *
 * @since $ver$
 * @template T The type of the value.
 * @extends Search_Field<T>
 */
abstract class Search_Field_Choices extends Search_Field {
	/**
	 * Whether the field has choices.
	 *
	 * @since $ver$
	 */
	protected function has_choices(): bool {
		return $this->get_choices() !== [];
	}

	/**
	 * Returns the choices for the field.
	 *
	 * @since $ver$
	 *
	 * @return array{text: string, value:string} The choices.
	 */
	abstract public function get_choices(): array;

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function to_template_data(): array {
		$data = parent::to_template_data();

		if ( $this->has_choices() ) {
			$data['choices'] = $this->get_choices();
		}

		return $data;
	}
}
