<?php

namespace GV\Search\Fields;

/**
 * Represents a search field that searches on the Entry Date.
 *
 * @since $ver$
 *
 * @extends Search_Field<string>
 */
final class Search_Field_Created_By extends Search_Field {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected string $icon = 'dashicons-admin-users';

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected static string $type = 'created_by';

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
		return esc_html__( 'Entry Creator', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_description(): string {
		return esc_html__( 'Search on entry creator', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_value(): string {
		return (string) parent::get_value();
	}
}
