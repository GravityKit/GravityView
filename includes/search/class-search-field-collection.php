<?php

namespace GV\Search;

use ArrayIterator;
use GV\Collection;
use GV\Collection_Position_Aware;
use GV\Plugin;
use GV\Search\Fields\Search_Field;
use GV\Search\Fields\Search_Field_All;
use GV\Search\Fields\Search_Field_Created_By;
use GV\Search\Fields\Search_Field_Entry_Date;
use GV\Search\Fields\Search_Field_Entry_ID;
use GV\Search\Fields\Search_Field_Is_Approved;
use GV\Search\Fields\Search_Field_Is_Read;
use GV\Search\Fields\Search_Field_Is_Starred;
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
	 * Micro cache to avoid multiple DB and filter calls.
	 *
	 * @since $ver$
	 *
	 * @var array<int, self>
	 */
	private static $available_fields_cache = [];

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
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return self
	 * @todo  add @filter
	 */
	public static function available_fields( int $form_id = 0 ): self {
		if ( $form_id > 0 && isset( self::$available_fields_cache[ $form_id ] ) ) {
			return self::$available_fields_cache[ $form_id ];
		}

		$fields = [
			new Search_Field_All(),
			new Search_Field_Entry_Date(),
			new Search_Field_Entry_ID(),
			new Search_Field_Created_By(),
			new Search_Field_Is_Starred(),
			new Search_Field_Is_Read(),
		];

		if ( gravityview()->plugin->supports( Plugin::FEATURE_GFQUERY ) ) {
			$fields[] = new Search_Field_Is_Approved();
		}

		$fields = (array) apply_filters( 'gk/gravityview/search/available-fields', $fields, $form_id );

		$collection = new self( array_filter( $fields, static fn( $field ) => $field instanceof Search_Field ) );

		self::$available_fields_cache[ $form_id ] = $collection;

		return $collection;
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

	/**
	 * Returns the class name of the search field, based on the provided type.
	 *
	 * @since $ver$
	 *
	 * @param string $type The search field type.
	 *
	 * @return string The class name.
	 */
	public function get_class_by_type( string $type ): string {
		foreach ( $this->storage as $field ) {
			$configuration = $field->to_configuration();

			if ( $type === $configuration['type'] ) {
				return get_class( $field );
			}
		}

		return '';
	}
}
