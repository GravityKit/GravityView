<?php

namespace GV\Search\Fields;

use GV\Search\Search_Field_Collection;

/**
 * Represents a single Search Field.
 *
 * @since $ver$
 * @template T The type of the value.
 */
abstract class Search_Field extends \GravityView_Admin_View_Item {
	/**
	 * The unique ID for this field.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected string $UID = '';

	/**
	 * The position.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	public string $position = '';

	/**
	 * A unique identifier for the Search Field type.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected string $type = 'unknown';

	/**
	 * The Field description.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected string $description = '';

	/**
	 * The icon.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected string $icon = '';

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
	public function __construct( ?string $label = null, array $data = [] ) {
		parent::__construct(
			$label ?? $this->get_label(),
			$this->type,
			$data,
		);

		$this->item['icon']        = $this->icon ? $this->icon : $this->item['icon'];
		$this->item['description'] = $this->get_description();
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
	public static function from_configuration( array $data ): ?Search_Field {
		// Can't instantiate the abstract class, but we can use it as a factory.
		if ( static::class === self::class ) {
			$types = iterator_to_array( Search_Field_Collection::available_fields() );
			$class = $types[ $data['id'] ?? '' ] ?? null;
			if ( ! $class instanceof self ) {
				return null;
			}

			return $class::from_configuration( $data );
		}

		$field = new static( $data['label'] ?? null, $data );

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
	 * Returns the field as a configuration array.
	 *
	 * @since $ver$
	 *
	 * @return array The configuration.
	 */
	public function to_configuration(): array {
		return [
			'type'     => $this->type,
			'label'    => $this->title,
			'value'    => $this->get_value(),
			'position' => $this->position,
			'UID'      => $this->UID,
		];
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_title( string $label ): string {
		return sprintf( __( 'Search Field: %s', 'gk-gravityview' ), $label );
	}

	/**
	 * Returns the label for this field.
	 *
	 * @since $ver$
	 */
	protected function get_label(): string {
		return $this->item['label'] ?? $this->title;
	}

	/**
	 * Returns the description for this field.
	 *
	 * @since $ver$
	 */
	protected function get_description(): string {
		return '';
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function additional_info(): array {
		$description = $this->get_description();
		if ( ! $description ) {
			return [];
		}

		$field_info_items = [
			[ 'value' => $this->item['description'] ],
		];

		return $field_info_items;
	}
}
