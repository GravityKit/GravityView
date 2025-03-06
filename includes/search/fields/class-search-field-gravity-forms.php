<?php

namespace GV\Search\Fields;

use GF_Field;
use GF_Fields;
use GFAPI;

/**
 * Represents a search field based on a Gravity Forms Field.
 *
 * @since $ver$
 *
 * @extends Search_Field<string>
 */
final class Search_Field_Gravity_Forms extends Search_Field_Choices {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected string $icon = 'dashicons-admin-site-alt3';

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
	 * Creates an instance based on a field object.
	 *
	 * @since $ver$
	 *
	 * @return self The instance.
	 */
	public static function from_field( array $field ): ?self {
		if ( empty( $field ) ) {
			return null;
		}

		$instance             = new self( $field['label'] ?? '' );
		$instance->form_field = $field;

		$instance->id   = $instance->get_type();
		$instance->icon = GF_Fields::create( $field )->get_form_editor_field_type_icon();

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
		return sprintf( '%s::%d::%s', self::$type, $this->form_field['form_id'] ?? 0, $this->form_field['id'] ?? 0 );
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
		$field_id = $this->get_field() ? $this->get_field()->id : 0;
		return sprintf( 'filter_%s', $field_id );
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

		return count( $field->choices ?? [] ) > 0;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_field_type(): string {
		return $this->has_choices() ? 'select' : parent::get_field_type();
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

		$field = GFAPI::get_field( $parts[1], $parts[2] );

		return $this->field = ( $field ?: null );
	}
}
