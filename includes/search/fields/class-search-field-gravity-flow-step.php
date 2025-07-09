<?php

namespace GV\Search\Fields;

use Gravity_Flow_API;
use Gravity_Flow_Step;
use GravityView_Field_Workflow_Step;
use GV\View;

/**
 * A search field for Gravity Flow Step fields.
 *
 * @since $ver$
 */
final class Search_Field_Gravity_Flow_Step extends Search_Field_Choices {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected static string $type = 'workflow_step';

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
		return esc_html__( 'Workflow Step', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_description(): string {
		return esc_html( 'Gravity Flow Step', 'gk-gravityview' );
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
		$string = '::workflow_step';

		return substr( $type, -strlen( $string ) ) === $string;
	}

	/**
	 * Returns the icon for the field.
	 *
	 * @since $ver$
	 *
	 * @return string
	 */
	protected function get_icon(): string {
		return ( new GravityView_Field_Workflow_Step() )->get_icon();
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
		return 'workflow_step';
	}

	/**
	 * @inheritDoc
	 * @since $ver$
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

	public static function from_configuration(
		array $data,
		?View $view = null,
		array $additional_context = []
	): ?Search_Field {
		return parent::from_configuration( $data, $view, $additional_context );
	}
}
