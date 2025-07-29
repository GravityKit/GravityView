<?php

namespace GV\Search\Fields;

/**
 * Represents a search field that searches on entry ID.
 *
 * @since 2.42
 *
 * @extends Search_Field<string>
 */
final class Search_Field_Entry_ID extends Search_Field {
	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected string $icon = 'dashicons-tag';

	/**
	 * @inheritdoc
	 * @since 2.42
	 */
	protected static string $type = 'entry_id';

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_name(): string {
		return esc_html__( 'Entry ID', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_default_label(): string {
		return esc_html__( 'Entry ID:', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	public function get_description(): string {
		return esc_html__( 'Search on entry ID', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_input_name(): string {
		return 'gv_id';
	}
}
