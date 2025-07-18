<?php

namespace GV\Search\Fields;

/**
 * Represents a search field that filters on starred entries.
 *
 * @since 2.42
 *
 * @extends Search_Field<string>
 */
final class Search_Field_Is_Starred extends Search_Field {
	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected string $icon = 'dashicons-star-half';

	/**
	 * @inheritdoc
	 * @since 2.42
	 */
	protected static string $type = 'is_starred';

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected static string $field_type = 'boolean';

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_name(): string {
		return esc_html__( 'Is Starred', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	public function get_description(): string {
		return esc_html__( 'Filter on starred entries', 'gk-gravityview' );
	}
}
