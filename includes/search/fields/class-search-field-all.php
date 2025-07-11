<?php

namespace GV\Search\Fields;

/**
 * Represents a search field that searches all fields.
 *
 * @since 2.42
 *
 * @extends Search_Field
 */
final class Search_Field_All extends Search_Field {
	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected string $icon = 'dashicons-admin-site-alt3';

	/**
	 * @inheritdoc
	 * @since 2.42
	 */
	protected static string $type = 'search_all';

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected static string $field_type = 'search_all';

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_name(): string {
		return esc_html__( 'Search Everything', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	public function get_description(): string {
		return esc_html__( 'Search across all entry fields', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_default_label(): string {
		return esc_html__( 'Search Entries:', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_options(): array {
		return [
			'placeholder' => [
				'type'     => 'text',
				'label'    => esc_html__( 'Placeholder text', 'gk-gravityview' ),
				'value'    => '',
				'class'    => 'widefat',
				'priority' => 1150,
			],
		];
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_input_name(): string {
		return 'gv_search';
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_input_type(): string {
		return 'search_all';
	}
}
