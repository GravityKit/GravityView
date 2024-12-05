<?php

namespace GV\Search;

use ArrayIterator;
use GV\Collection;
use GV\Collection_Position_Aware;
use GV\Search\Fields\Search_Field;
use GV\Search\Fields\Search_Field_All;
use GV\Search\Fields\Search_Field_Text;
use IteratorAggregate;

/**
 * Represents a collection of search fields.
 *
 * @since $ver$
 *
 * @extends Collection<Search_Field>
 */
final class Search_Field_Collection extends Collection implements Collection_Position_Aware, IteratorAggregate {
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
	public static function available_fields(): self {
		$fields = (array) apply_filters(
			'gk/gravityview/search/collection/available-fields',
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
		$collection = new self();

		foreach ( $configuration as $position => $_fields ) {
			if ( ! $_fields ) {
				continue;
			}

			foreach ( $_fields as $uid => $_configuration ) {
				$_configuration['UID']      = $uid;
				$_configuration['position'] = $position;

				$field = Search_Field::from_configuration( $_configuration );
				if ( ! $field ) {
					continue;
				}

				$collection->add( $field );
			}
		}

		return $collection;
	}

	/**
	 * Returns the search field collection as a configuration array.
	 *
	 * @since $ver$
	 *
	 * @return array
	 */
	public function to_configuration(): array {
		$configuration = [];
		foreach ( $this->storage as $field ) {
			$data = $field->to_configuration();
			if ( ! isset( $data['position'], $data['UID'] ) ) {
				continue;
			}

			$configuration[ $data['position'] ]                 ??= [];
			$configuration[ $data['position'] ][ $data['UID'] ] = $data;
		}

		return $configuration;
	}

	/**
	 * Returns The iterator.
	 *
	 * @since $ver$
	 *
	 * @return ArrayIterator<Search_Field>
	 */
	public function getIterator(): ArrayIterator {
		$fields = [];

		foreach ( $this->storage as $field ) {
			$configuration                    = $field->to_configuration();
			$fields[ $configuration['type'] ] = $field;
		}

		return new ArrayIterator( $fields );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function by_position( $position ) {
		$clone = clone $this;

		$search         = implode( '.*', array_map( 'preg_quote', explode( '*', $position ) ) );
		$clone->storage = array_filter(
			$clone->storage,
			static fn( Search_Field $field ): bool => preg_match( "#^{$search}$#", $field->position ),
		);

		return $clone;
	}
}
