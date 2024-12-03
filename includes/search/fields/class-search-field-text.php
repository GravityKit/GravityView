<?php

namespace GV\Search\Fields;

/**
 * Represents a search field that searches a single field.
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
	protected string $type = 'input_text';

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
	protected $search_field = '';

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function __construct() {
		parent::__construct();

		$this->label = esc_html__( 'Text', 'gk-gravityview' );
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
	public function to_array(): array {
		return array_merge(
			parent::to_array(),
			[
				'search_field' => $this->search_field,
			]
		);
	}
}
