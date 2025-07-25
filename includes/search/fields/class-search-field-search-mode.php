<?php

namespace GV\Search\Fields;

/**
 * Represents a search mode field.
 *
 * @since 2.42
 */
final class Search_Field_Search_Mode extends Search_Field_Choices {
	/**
	 * The available modes.
	 *
	 * @since 2.42
	 */
	private const MODE_ALL = 'all';
	private const MODE_ANY = 'any';

	/**
	 * The default mode.
	 *
	 * @since 2.42
	 */
	private const MODE_DEFAULT = self::MODE_ALL;

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected string $icon = 'dashicons-filter';

	/**
	 * @inheritdoc
	 * @since 2.42
	 */
	protected static string $type = 'search_mode';

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected static string $field_type = 'search_mode';

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function setting_keys(): array {
		$keys   = parent::setting_keys();
		$keys[] = 'mode';

		return $keys;
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_name(): string {
		return esc_html__( 'Search Mode', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	public function get_description(): string {
		return esc_html__( 'Should search results match all search fields, or any?', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_default_label(): string {
		return esc_html__( 'Search Mode', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_options(): array {
		return [
			'mode' => [
				'type'     => 'select',
				'class'    => 'widefat',
				'label'    => esc_html__( 'Search Mode', 'gk-gravityview' ),
				'desc'     => __( 'Should search results match all search fields, or any?', 'gk-gravityview' ),
				'value'    => self::MODE_DEFAULT,
				'choices'  => array_column( $this->get_choices(), 'text', 'value' ),
				'priority' => 1200,
			],
		];
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_input_name(): string {
		return 'mode';
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_input_value(): string {
		$stored_value = $this->settings['mode'] ?? self::MODE_DEFAULT;
		if ( 'hidden' === ( $this->settings['input_type'] ?? 'hidden' ) ) {
			return $stored_value;
		}

		$value = parent::get_input_value();

		return (string) $value ? $value : $stored_value;
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	public function has_request_value(): bool {
		if (
			'hidden' === ( $this->settings['input_type'] ?? 'hidden' )
			|| ! isset( $_REQUEST[ $this->get_input_name() ] )
			|| '' === $this->get_request_value( $this->get_input_name() )
		) {
			return false;
		}

		$stored_value = $this->settings['mode'] ?? self::MODE_DEFAULT;

		return $this->get_input_value() !== $stored_value;
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function has_choices(): bool {
		return 'hidden' !== ( $this->settings['input_type'] ?? 'hidden' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_choices(): array {
		return [
			[
				'text'  => esc_html__( 'Match Any Fields', 'gk-gravityview' ),
				'value' => self::MODE_ANY,
			],
			[
				'text'  => esc_html__( 'Match All Fields', 'gk-gravityview' ),
				'value' => self::MODE_ALL,
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
