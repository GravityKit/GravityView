<?php

namespace GV\Search\Fields;

use Gravity_Flow_API;
use Gravity_Flow_Step;
use GravityView_Field_Workflow_Step;

/**
 * A search field for Gravity Flow Step fields.
 *
 * @since 2.42
 */
final class Search_Field_Gravity_Flow_Step extends Search_Field_Choices {
	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected static string $type = 'workflow_step';

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected static string $field_type = 'select';

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_name(): string {
		return esc_html__( 'Workflow Step', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	public function get_description(): string {
		return esc_html( 'Gravity Flow Step', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 *
	 * Make only allow named constructors for this field, due to the dependencies.
	 *
	 * @since 2.42
	 */
	protected function __construct( ?string $label = null, array $data = [] ) {
		// Do not initialize here, it will be called on the named constructors to parse dependencies.
		parent::__construct( $label, $data, false );

		$this->form_id ??= $data['form_id'] ?? null;
		$this->id        = $this->get_type();
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	public function satisfies( array $configuration ): bool {
		if ( $this->is_of_type( $configuration['id'] ?? '' ) ) {
			return true;
		}

		return parent::satisfies( $configuration );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	public function is_of_type( string $type ): bool {
		$string = self::$type;

		return preg_match( '/' . preg_quote( $string, '/' ) . '$/is', $type );
	}

	/**
	 * Returns the icon for the field.
	 *
	 * @since 2.42
	 *
	 * @return string
	 */
	protected function get_icon(): string {
		return ( new GravityView_Field_Workflow_Step() )->get_icon();
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	public function get_type(): string {
		return sprintf( '%d::%s', $this->form_id, parent::get_type() );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_key(): string {
		return self::$type;
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_choices(): array {
		$gravity_flow_api = new Gravity_Flow_API( $this->form_id );
		$workflow_steps   = $gravity_flow_api->get_steps();
		if ( ! $workflow_steps ) {
			return [];
		}

		return array_map(
			static fn( Gravity_Flow_Step $step ): array => [
				'text'  => $step->get_name(),
				'value' => $step->get_id(),
			],
			$workflow_steps
		);
	}
}
