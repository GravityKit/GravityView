<?php

namespace GV\Search\Fields;

/**
 * Represents a Submit Button field for search forms.
 *
 * @since 2.42
 */
final class Search_Field_Submit extends Search_Field {
	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected string $icon = 'dashicons-button';

	/**
	 * @inheritdoc
	 * @since 2.42
	 */
	protected static string $type = 'submit';

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected static string $field_type = 'submit';

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_name(): string {
		return esc_html__( 'Submit Button', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	public function get_description(): string {
		return esc_html__( 'Button to submit the search', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_default_label(): string {
		return esc_html__( 'Search', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function setting_keys(): array {
		$keys   = parent::setting_keys();
		$keys[] = 'search_clear';
		$keys[] = 'tag';

		return $keys;
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_options(): array {
		return [
			'show_label'   => [
				'type'  => 'hidden',
				'value' => 1,
			],
			'search_clear' => [
				'type'     => 'checkbox',
				'label'    => esc_html__( 'Show Clear button', 'gk-gravityview' ),
				'desc'     => esc_html__(
					'When a search is performed, display a button that removes all search values.',
					'gk-gravityview'
				),
				'value'    => true,
				'priority' => 1050,
			],
			'tag'          => [
				'type'     => 'select',
				'label'    => esc_html__( 'Button HTML-tag', 'gk-gravityview' ),
				'value'    => 'input',
				'class'    => 'widefat',
				'choices'  => [
					'input'  => esc_html__( 'input (default)', 'gk-gravityview' ),
					'button' => esc_html__( 'button', 'gk-gravityview' ),
				],
				'priority' => 1150,
			],
		];
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	public function is_searchable_field(): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function init(): void {
		parent::init();

		$this->settings['show_label'] = true;
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function is_allowed_once(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function allowed_sections(): array {
		return array_diff(
			parent::allowed_sections(),
			[ 'search-advanced' ] // Remove the advanced search section.
		);
	}
}
