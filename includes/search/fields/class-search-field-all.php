<?php

namespace GV\Search\Fields;

/**
 * Represents a search field that searches all fields.
 *
 * @since $ver$
 *
 * @extends Search_Field<string>
 */
final class Search_Field_All extends Search_Field {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected string $icon = 'dashicons-admin-site-alt3';

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected string $type = 'all';

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
	public function __construct() {
		parent::__construct(
			esc_html__( 'Search Everything', 'gk-gravityview' ),
		);
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_value(): string {
		return (string) parent::get_value();
	}
}
