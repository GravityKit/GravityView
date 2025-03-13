<?php

namespace GV\Search\Fields;

use GF_Field;
use GF_Fields;
use GFAPI;
use GravityView_Widget_Search;
use GV\View;

/**
 * Represents a search field based on a Gravity Forms Field.
 *
 * @since $ver$
 *
 * @extends Search_Field<string>
 */
final class Search_Field_Gravity_Forms extends Search_Field_Choices {
	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected static string $type = 'gravity_forms';

	/**
	 * The inner field object.
	 *
	 * @since $ver$
	 *
	 * @var array
	 */
	public array $form_field = [];

	/**
	 * Microcache of the connected field instance.
	 *
	 * @since $ver$
	 *
	 * @var GF_Field|null
	 */
	private ?GF_Field $field = null;

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
	 * Creates an instance based on a field object.
	 *
	 * @since $ver$
	 *
	 * @param array $field The field object.
	 *
	 * @return self The instance.
	 */
	public static function from_field( array $field ): ?self {
		if ( empty( $field ) ) {
			return null;
		}

		$instance             = new self( $field['label'] ?? '' );
		$instance->form_field = $field;
		$gf_field             = GF_Fields::create( $field );
		if ( $gf_field ) {
			$instance->field = $gf_field;
		}

		$instance->id = $instance->get_type();

		$instance->init();

		return $instance;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public static function from_configuration( array $data, ?View $view = null ): ?self {
		$instance = parent::from_configuration( $data, $view );
		if ( ! $instance ) {
			return null;
		}

		$instance->init();

		return $instance;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_label(): string {
		return esc_html__( 'Gravity Forms Field', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_description(): string {
		return esc_html__( 'Gravity Forms Field', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function to_configuration(): array {
		return array_merge(
			parent::to_configuration(),
			[
				'form_field' => $this->form_field,
			]
		);
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_type(): string {
		if ( isset( $this->item['id'] ) ) {
			return $this->item['id'];
		}

		return sprintf( '%s::%d::%s', self::$type, $this->form_field['form_id'] ?? 0, $this->form_field['id'] ?? 0 );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function init(): void {
		parent::init();

		$field = $this->get_field();
		if ( ! $field ) {
			return;
		}

		$this->item['icon']   = $field->get_form_editor_field_type_icon();
		$this->item['parent'] = $field['parent'] ?? null;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function is_of_type( string $type ): bool {
		return $this->get_type() === $type;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_input_name(): string {
		$field_id = $this->get_field_id();

		return sprintf( 'filter_%s', str_replace( '.', '_', $field_id ) );
	}

	/**
	 * Returns the field ID.
	 *
	 * @since $ver$
	 *
	 * @return string The field ID.
	 */
	private function get_field_id(): string {
		$parts = explode( '::', (string) ( $this->id ?? '' ) );
		if ( count( $parts ) !== 3 ) {
			return '0';
		}

		return (string) $parts[2];
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function has_choices(): bool {
		$field = $this->get_field();
		if ( ! $field ) {
			return false;
		}

		$choices = $field->choices ?? [];

		return is_array( $choices ) && count( $choices ) > 0;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_field_type(): string {
		$field = $this->get_field();
		if ( ! $field ) {
			return parent::get_field_type();
		}

		return GravityView_Widget_Search::get_search_input_types( $this->get_field_id(), $field['type'] );
	}

	/**
	 * @inheritDoc@
	 * @since     $ver$
	 */
	public function get_choices(): array {
		$field = $this->get_field();
		if ( ! $field ) {
			return [];
		}

		return $field->choices ?? [];
	}

	/**
	 * Retrieve the Gravity Forms field connected to this search field.
	 *
	 * @since $ver$
	 *
	 * @return GF_Field|null The Gravity Forms field.
	 */
	private function get_field(): ?GF_Field {
		if ( $this->field ) {
			return $this->field;
		}

		$parts = explode( '::', (string) ( $this->id ?? '' ) );
		if ( count( $parts ) !== 3 ) {
			return null;
		}

		$field       = GFAPI::get_field( $parts[1], $parts[2] );
		$this->field = $field ? $field : null;

		return $this->field;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_key(): string {
		$field_id = $this->get_field_id();
		if ( ! $field_id ) {
			return parent::get_key();
		}

		return $field_id;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_input_value() {
		$value = parent::get_input_value();
		if ( empty( $value ) ) {
			if ( 'date_range' === $this->get_input_type() ) {
				$value = [
					'start' => '',
					'end'   => '',
				];
			} elseif ( 'number_range' === $this->get_input_type() ) {
				$value = [
					'min' => '',
					'max' => '',
				];
			}
		}

		return $value;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function to_legacy_format(): array {
		$data = parent::to_legacy_format();

		$field = $this->get_field();
		if ( $field ) {
			$data['form_id'] = $field['formId'] ?? null;
		}

		return $data;
	}
}
