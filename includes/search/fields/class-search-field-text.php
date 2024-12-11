<?php

namespace GV\Search\Fields;

/**
 * Represents a search field that searches by text.
 *
 * @since $ver$
 *
 * @extends Search_Field<string>
 */
final class Search_Field_Text extends Search_Field {
	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected static string $type = 'text';

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected string $icon = 'dashicons-search';

	/**
	 * @inheritdoc
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected $value = '';

	/**
	 * The field ID to search.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected string $search_field = '';

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_description(): string {
		return esc_html__( 'Search input field', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_label(): string {
		return esc_html__( 'Text', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_value(): string {
		return (string) parent::get_value();
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function to_configuration(): array {
		return array_merge(
			parent::to_configuration(),
			[
				'search_field' => $this->search_field,
			]
		);
	}
}
