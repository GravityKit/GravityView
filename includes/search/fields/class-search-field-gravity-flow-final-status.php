<?php

namespace GV\Search\Fields;

use GravityView_Field_Workflow_Final_Status;

/**
 * A search field for Gravity Flow Final Status.
 *
 * @since $ver$
 */
final class Search_Field_Gravity_Flow_Final_Status extends Search_Field_Choices {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected static string $type = 'workflow_final_status';

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
		return esc_html__( 'Workflow Status', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_description(): string {
		return esc_html( 'Gravity Flow Final Status', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 *
	 * Make only allow named constructors for this field, due to the dependencies.
	 *
	 * @since $ver$
	 */
	protected function __construct( ?string $label = null, array $data = [] ) {
		// Do not initialize here, it will be called on the named constructors to parse dependencies.
		parent::__construct( $label, $data, false );

		$this->form_id ??= $data['form_id'] ?? null;
		$this->id        = $this->get_type();
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function satisfies( array $configuration ): bool {
		if ( $this->is_of_type( $configuration['id'] ?? '' ) ) {
			return true;
		}

		return parent::satisfies( $configuration );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function is_of_type( string $type ): bool {
		$string = self::$type;

		return preg_match( '/' . preg_quote( $string, '/' ) . '$/is', $type );
	}

	/**
	 * Returns the icon for the field.
	 *
	 * @since $ver$
	 *
	 * @return string
	 */
	protected function get_icon(): string {
		return ( new GravityView_Field_Workflow_Final_Status() )->get_icon();
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_type(): string {
		return sprintf( '%d::%s', $this->form_id, parent::get_type() );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_key(): string {
		return self::$type;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_choices(): array {
		$gravity_flow = gravity_flow();
		if ( ! $gravity_flow ) {
			return [];
		}
		$entry_meta = $gravity_flow->get_entry_meta( [], $this->form_id );

		return (array) \GV\Utils::get( $entry_meta, self::$type . '/filter/choices' );
	}
}
