<?php

namespace GV\Search\Fields;

/**
 * Represents a search field that searches all fields.
 *
 * @since $ver$
 *
 * @extends Search_Field
 */
final class Search_Field_Submit extends Search_Field {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected string $icon = 'dashicons-search';

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected static string $type = 'submit';

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected static string $field_type = 'submit';

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_label(): string {
		return esc_html__( 'Submit button', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_description(): string {
		return esc_html__( 'Button to submit the search', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_default_label(): string {
		return esc_html__( 'Search', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_options(): array {
		return [
			'show_label'   => [
				'type'  => 'hidden',
				'value' => 1,
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
		];
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function to_template_data(): array {
		$data        = parent::to_template_data();
		$data['tag'] = $this->item['tag'] ?? 'input';

		return $data;
	}
}
