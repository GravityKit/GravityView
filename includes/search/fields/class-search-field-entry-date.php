<?php

namespace GV\Search\Fields;

/**
 * Represents a search field that searches on the Entry Date.
 *
 * @since $ver$
 *
 * @extends Search_Field<array{start:string, end:string}>
 */
final class Search_Field_Entry_Date extends Search_Field {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected string $icon = 'dashicons-calendar-alt';

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected static string $type = 'entry_date';

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected static string $field_type = 'entry_date';

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_name(): string {
		return esc_html__( 'Entry Date', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_default_label(): string {
		return esc_html__( 'Filter by date:', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_description(): string {
		return esc_html__( 'Search on entry date within a range', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_input_value() {

		if ( $this->get_input_type() === 'date_range' ) {
			return [
				'start' => $this->get_request_value( 'gv_start', '' ),
				'end'   => $this->get_request_value( 'gv_end', '' ),
			];
		}

		return $this->get_request_value( 'filter_entry_date', '' );
	}
}
