<?php

namespace GV\Search\Fields;

/**
 * Represents a search field that searches on entry ID.
 *
 * @since $ver$
 *
 * @extends Search_Field<string>
 */
final class Search_Field_Entry_ID extends Search_Field {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected string $icon = 'dashicons-tag';

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected static string $type = 'entry_id';

	/**
	 * @inheritdoc
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected $value = '';

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_label(): string {
		return esc_html__( 'Entry ID', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_description(): string {
		return esc_html__( 'Search on entry ID', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_value(): string {
		return (string) parent::get_value();
	}
}
