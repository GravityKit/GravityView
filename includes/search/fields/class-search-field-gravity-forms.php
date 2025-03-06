<?php

namespace GV\Search\Fields;

use GF_Fields;

/**
 * Represents a search field based on a Gravity Forms Field.
 *
 * @since $ver$
 *
 * @extends Search_Field<string>
 */
final class Search_Field_Gravity_Forms extends Search_Field {
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
		return sprintf( 'filter_%s', $this->form_field['id'] );
	}
}
