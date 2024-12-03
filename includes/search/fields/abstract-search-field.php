<?php

namespace GV\Search\Fields;

/**
 * Represents a single Search Field.
 *
 * @since $ver$
 * @template T The type of the value.
 */
abstract class Search_Field {
	/**
	 * A unique identifier for the Search Field type.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected string $type = 'unknown';

	/**
	 * The label of the field.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected string $label;

	/**
	 * The value.
	 *
	 * @since $ver$
	 *
	 * @var T
	 */
	protected $value;

	/**
	 * Creates the base field instance.
	 *
	 * @since $ver$
	 */
	public function __construct() {
		$this->label = esc_html__( 'Unknown Field', 'gk-gravityview' );
	}

	/**
	 * Returns the Field instance based on the data.
	 *
	 * @since $ver$
	 *
	 * @param array $data The field data.
	 *
	 * @return static|null The field instance.
	 */
	public static function from_array( array $data ): ?Search_Field {
		$field = new static();

		unset( $data['type'] );

		foreach ( $data as $key => $value ) {
			if ( property_exists( $field, $key ) ) {
				$field->{$key} = $value;
			}
		}

		return $field;
	}

	/**
	 * Returns the value of the field as the correc type.
	 *
	 * @since $ver$
	 *
	 * @return mixed
	 */
	protected function get_value() {
		return $this->value;
	}

	/**
	 * Returns the field as a
	 *
	 * @return array
	 */
	public function to_array(): array {
		return [
			'type'  => $this->type,
			'label' => $this->label,
			'value' => $this->get_value(),
		];
	}
}
