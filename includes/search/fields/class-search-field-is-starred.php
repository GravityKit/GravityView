<?php

namespace GV\Search\Fields;

/**
 * Represents a search field that filters on starred entries.
 *
 * @since $ver$
 *
 * @extends Search_Field<string>
 */
final class Search_Field_Is_Starred extends Search_Field {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected string $icon = 'dashicons-star-half';

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected static string $type = 'is_starred';

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected static string $field_type = 'boolean';

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_name(): string {
		return esc_html__( 'Is Starred', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_description(): string {
		return esc_html__( 'Filter on starred entries', 'gk-gravityview' );
	}
}
