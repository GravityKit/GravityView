<?php

namespace GV\Search\Fields;

use GravityView_Entry_Approval_Status;

/**
 * Represents a search field that searches on the Entry Date.
 *
 * @since $ver$
 *
 * @extends Search_Field<string>
 */
final class Search_Field_Is_Approved extends Search_Field_Choices {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected string $icon = 'dashicons-yes-alt';

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected static string $type = 'is_approved';

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
		return esc_html__( 'Approval Status', 'gk-gravityview' );
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
	protected function get_value(): string {
		return (string) parent::get_value();
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_choices(): array {
		return array_map(
			static fn( array $choice ): array => [
				'text'  => $choice['label'] ?? '',
				'value' => $choice['value'] ?? '',
			],
			GravityView_Entry_Approval_Status::get_all()
		);
	}
}
