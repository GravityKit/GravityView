<?php

namespace GV\Search;

use GV\Collection;
use GV\Search\Fields\Search_Field;
use GV\Search\Fields\Search_Field_All;
use GV\Search\Fields\Search_Field_Text;

/**
 * Represents a collection of search fields.
 *
 * @since $ver$
 *
 * @extends Collection<Search_Field>
 */
final class Search_Field_Collection extends Collection {
	/**
	 * Creates a collection of fields.
	 *
	 * @since $ver$
	 *
	 * @param Search_Field[] $fields The fields.
	 */
	private function __construct( array $fields = [] ) {
		$this->storage = $fields;
	}

	/**
	 * Returns the default search fields.
	 *
	 * @since $ver$
	 * @return self
	 * @todo  add @filter
	 */
	public static function default(): self {
		$fields = (array) apply_filters(
			'gk/gravityview/search/collection/default',
			[
				new Search_Field_All(),
				new Search_Field_Text(),
			]
		);

		return new self( array_filter( $fields, static fn( $field ) => $field instanceof Search_Field ) );
	}

	/**
	 * Returns a collection from a stored configuration.
	 *
	 * @since $ver$
	 *
	 * @param array $configuration The configuration.
	 *
	 * @return self|null The collection.
	 */
	public static function from_configuration( array $configuration ): self {
		$fields     = [];
		$collection = self::default();
		foreach ( $collection->all() as $field ) {
			$data                    = $field->to_array();
			$fields[ $data['type'] ] = $field;
		}

		$active_fields = array_filter(
			array_map(
				static function ( array $field ) use ( $fields ): ?Search_Field {
					$class = $fields[ $field['type'] ?? '' ] ?? null;
					if ( ! $class instanceof Search_Field ) {
						return null;
					}

					return $class::from_array( $field );
				},
				$configuration
			)
		);

		return new self( $active_fields );
	}

	/**
	 * Returns the search field collection as a configuration array.
	 *
	 * @since $ver$
	 *
	 * @return array
	 */
	public function to_configuration(): array {
		return array_map(
			static fn( Search_Field $field ) => $field->to_array(),
			$this->storage
		);
	}
}
