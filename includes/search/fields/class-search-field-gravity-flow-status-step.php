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
final class Search_Field_Gravity_Flow_Status_Step extends Search_Field_Choices {
	/**
	 * The unique type for workflow fields.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected static string $type = 'workflow_status_step';

	/**
	 * The inner step.
	 *
	 * @since $ver$
	 *
	 * @var Gravity_Flow_Step|null
	 */
	private ?Gravity_Flow_Step $step = null;

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected static string $field_type = 'select';

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
	}

	/**
	 * Creates an instance from a Gravity Flow step.
	 *
	 * @since $ver$
	 *
	 * @param Gravity_Flow_Step $step The step.
	 *
	 * @return self|null
	 */
	public static function from_step( Gravity_Flow_Step $step ): self {
		$label = sprintf(
			_x( 'Status: %s', 'Gravity Flow Workflow Step Status', 'gk-gravityview' ),
			$step->get_name()
		);

		$instance       = new self( $label );
		$instance->step = $step;

		$instance->id         = $instance->get_type();
		$instance->item['id'] = $instance->id;

		$instance->init();

		return $instance;
	}

	/**
	 * Retrieves the step ID from the field ID.
	 *
	 * @since $ver$
	 *
	 * @return int|null
	 */
	private function get_step_id(): ?int {
		if ( $this->step ) {
			return $this->step->get_id();
		}

		[ , $step_id ] = sscanf( $this->id, '%d::workflow_step_status_%d' );

		return $step_id ?? null;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public static function from_configuration(
		array $data,
		?View $view = null,
		array $additional_context = []
	): ?Search_Field {
		$instance = parent::from_configuration( $data, $view, $additional_context );
		if ( ! $instance ) {
			return null;
		}

		// Retrieve the step field for this search field.
		$gravity_flow_api = new Gravity_Flow_API( $instance->form_id );

		$step           = $gravity_flow_api->get_step( $instance->get_step_id() );
		$instance->step = $step ? $step : null;

		return $instance;
	}

	/**
	 * Returns the workflow step ID.
	 *
	 * @since $ver$
	 *
	 * @return string|null The workflow step ID.
	 */
	private function step_status_id(): ?string {
		if ( ! $this->step ) {
			return null;
		}

		return sprintf( 'workflow_step_status_%d', $this->step->get_id() );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_type(): string {
		if ( ! $this->step ) {
			return parent::get_type();
		}

		return sprintf( '%d::%s', $this->step->get_form_id(), $this->step_status_id() );
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
	 * Returns the description for this field.
	 *
	 * @since $ver$
	 */
	public function get_description(): string {
		return esc_html__( 'Gravity Flow Step Status', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function is_of_type( string $type ): bool {
		return strpos( $type, '::workflow_' ) > 0;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_key(): string {
		$field_id = $this->step_status_id();

		return $field_id ?? parent::get_key();
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
	protected function get_choices(): array {
		if (
			! $this->step
			|| null === $this->step_status_id()
		) {
			return [];
		}

		$entry_meta = gravity_flow()->get_entry_meta( [], $this->step->get_form_id() );

		return (array) \GV\Utils::get( $entry_meta, $this->step_status_id() . '/filter/choices' );
	}
}
