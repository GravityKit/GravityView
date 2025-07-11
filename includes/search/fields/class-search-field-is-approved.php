<?php

namespace GV\Search\Fields;

use GravityView_Entry_Approval_Status;

/**
 * Represents a search field that searches on the Entry Date.
 *
 * @since 2.42
 *
 * @extends Search_Field<string>
 */
final class Search_Field_Is_Approved extends Search_Field_Choices {
	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected string $icon = 'dashicons-yes-alt';

	/**
	 * @inheritdoc
	 * @since 2.42
	 */
	protected static string $type = 'is_approved';

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected static string $field_type = 'multi';

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_name(): string {
		return esc_html__( 'Approval Status', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	public function get_description(): string {
		return esc_html__( 'Filter on approval status', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_default_label(): string {
		return esc_html__( 'Approval:', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_choices(): array {
		return array_map(
			static fn( array $choice ): array => [
				'text'  => (string) ( $choice['label'] ?? '' ),
				'value' => (string) ( $choice['value'] ?? '' ),
			],
			GravityView_Entry_Approval_Status::get_all()
		);
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_input_name(): string {
		return 'is_approved';
	}
}
