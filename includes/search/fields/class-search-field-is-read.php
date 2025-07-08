<?php

namespace GV\Search\Fields;

/**
 * Represents a search field that filters entries by read status.
 *
 * @since $ver$
 *
 * @extends Search_Field<string>
 */
final class Search_Field_Is_Read extends Search_Field_Choices {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected string $icon = 'dashicons-visibility';

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected static string $type = 'is_read';

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected static string $field_type = 'select';

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_name(): string {
		return esc_html__( 'Is Read', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_description(): string {
		return esc_html__( 'Filter on read entries', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_choices(): array {
		return [
			[
				'text'  => __( 'Read', 'gk-gravityview' ),
				'value' => '1',
			],
			[
				'text'  => __( 'Unread', 'gk-gravityview' ),
				'value' => '0',
			],
		];
	}
}
