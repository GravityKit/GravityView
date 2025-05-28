<?php

namespace GV\Search;

use ArrayIterator;
use GravityView_Widget_Search;
use GV\Collection;
use GV\Collection_Position_Aware;
use GV\Grid;
use GV\Plugin;
use GV\Search\Fields\Search_Field;
use GV\Search\Fields\Search_Field_All;
use GV\Search\Fields\Search_Field_Created_By;
use GV\Search\Fields\Search_Field_Entry_Date;
use GV\Search\Fields\Search_Field_Entry_ID;
use GV\Search\Fields\Search_Field_Gravity_Forms;
use GV\Search\Fields\Search_Field_Is_Approved;
use GV\Search\Fields\Search_Field_Is_Read;
use GV\Search\Fields\Search_Field_Is_Starred;
use GV\Search\Fields\Search_Field_Search_Mode;
use GV\Search\Fields\Search_Field_Submit;
use GV\Template_Context;
use GV\View;
use IteratorAggregate;
use JsonException;

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
	 * Contains any additional context used for filters.
	 *
	 * @since $ver$
	 *
	 * @var array
	 */
	private array $context;

	/**
	 * Creates a collection of fields.
	 *
	 * @since $ver$
	 *
	 * @param Search_Field[] $fields  The fields.
	 * @param array          $context The additional context.
	 */
	private function __construct( array $fields = [], array $context = [] ) {
		$this->storage = $fields;
		$this->context = $context;
	}

	/**
	 * Returns the default search fields.
	 *
	 * @since $ver$
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return self
	 */
	public static function available_fields( int $form_id = 0 ): self {
		if ( $form_id > 0 && isset( self::$available_fields_cache[ $form_id ] ) ) {
			return self::$available_fields_cache[ $form_id ];
		}

		$fields = [
			new Search_Field_All(),
			new Search_Field_Search_Mode(),
			new Search_Field_Submit(),
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
	 * @param array     $configuration      The configuration.
	 * @param View|null $view               The View object.
	 * @param array     $additional_context Additional params passed along to every field (e.g. Context, Widget args).
	 *
	 * @return self The collection.
	 */
	public static function from_configuration(
		array $configuration,
		?View $view = null,
		array $additional_context = []
	): self {
		$collection = new self( [], $additional_context );

		foreach ( $configuration as $position => $_fields ) {
			if ( ! $_fields ) {
				continue;
			}

			foreach ( $_fields as $uid => $_configuration ) {
				$_configuration['UID']      = $uid;
				$_configuration['position'] = $position;

				$field = Search_Field::from_configuration( $_configuration, $view, $additional_context );
				if ( ! $field ) {
					continue;
				}

				$collection->add( $field );
			}
		}

		return $collection;
	}

	/**
	 * Creates a collection based on the legacy configuration.
	 *
	 * @since $ver$
	 *
	 * @param array     $configuration      The legacy configuration.
	 * @param View|null $view               The View.
	 * @param array     $additional_context Additional params passed along to every field (e.g. Context, Widget args).
	 *
	 * @return self The collection.
	 */
	public static function from_legacy_configuration(
		array $configuration,
		?View $view,
		array $additional_context = []
	): self {
		$collection = new self( [], $additional_context );

		try {
			$search_fields = json_decode( $configuration['search_fields'] ?? '[]', true, 512, JSON_THROW_ON_ERROR );
		} catch ( JsonException $e ) {
			return $collection;
		}

		if ( [] === $search_fields ) {
			return $collection;
		}

		$row = 'horizontal' === ( $configuration['search_layout'] ?? null )
			? Grid::get_row_by_type( '50/50' )
			: Grid::get_row_by_type( '100' );

		if ( [] === $row ) {
			return $collection;
		}

		$form_id = (int) ( $view ? $view->form->ID : ( $configuration['form_id'] ?? 0 ) );

		$shared_data = [
			'sieve_choices' => (bool) ( $configuration['sieve_choices'] ?? false ),
		];

		$areas      = array_keys( $row );
		$area_count = count( $areas );

		// Transform legacy fields into Search Fields.
		foreach ( $search_fields as $i => $field ) {
			// Automatically loop through all available areas.
			$area_key = $areas[ $i % $area_count ];

			$field_id = (string) ( $field['field'] ?? '' );
			$form_id  = (string) ( $field['form_id'] ?? $form_id );

			$field_data = array_merge(
				$shared_data,
				[
					'id'           => $field_id,
					'form_id'      => $form_id,
					'input_type'   => $field['input'] ?? 'input_text',
					'custom_label' => $field['label'] ?? '',
					'position'     => 'search-general_' . ( $row[ $area_key ][0]['areaid'] ?? '' ),
				]
			);

			$search_field = Search_Field::from_configuration( $field_data, $view, $additional_context );
			if ( ! $search_field ) {
				$field_data['id'] = Search_Field_Gravity_Forms::generate_field_id( $form_id, $field_id );
				$search_field     = Search_Field::from_configuration( $field_data, $view, $additional_context );
			}

			if ( $search_field ) {
				$collection->add( $search_field );
			}
		}

		// Only add the required fields if there are search fields.
		if ( $collection->count() > 0 ) {
			$collection = $collection->ensure_required_search_fields( $configuration );
		}

		return $collection;
	}

	/**
	 * Return the available field instance by field ID, if it exists.
	 *
	 * @since $ver$
	 *
	 * @param int    $form_id  The form ID.
	 * @param string $field_id The field ID.
	 *
	 * @return Search_Field|null The field.
	 */
	public static function get_field_by_field_id( int $form_id, string $field_id ): ?Search_Field {
		$available_fields = self::available_fields( $form_id );

		foreach ( $available_fields as $field ) {
			if ( $field->is_of_type( $field_id ) ) {
				return $field;
			}
		}

		return null;
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
	 * Returns whether this collection contains a date field.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether the collection contains a date field.
	 */
	public function has_date_field(): bool {
		$date_field_types = [ 'date', 'date_range', 'entry_date' ];

		foreach ( $this->storage as $field ) {
			$input_type = $field->to_template_data()['input'] ?? '';
			if ( in_array( $input_type, $date_field_types, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns whether one of the visible fields has a request value.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether one of the visible fields has a request value.
	 */
	public function has_request_values(): bool {
		foreach ( $this->storage as $field ) {
			if ( ! $field->is_visible() ) {
				continue;
			}

			if ( $field->has_request_value() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns whether the current collection has any fields of the provided type.
	 *
	 * @since $ver$
	 *
	 * @param string $type The type to check.
	 *
	 * @return bool Whether the field type is in the collection.
	 */
	public function has_fields_of_type( string $type ): bool {
		foreach ( $this->storage as $field ) {
			if ( $field->is_of_type( $type ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Ensures required fields are added to the collection.
	 *
	 * @since $ver$
	 *
	 * @param array $configuration The configuration.
	 *
	 * @return self A new collection with the required fields.
	 */
	public function ensure_required_search_fields( array $configuration = [] ): self {
		$collection = clone $this;

		// Add Submit and Search Mode fields in a separate row.
		$submit_row           = Grid::get_row_by_type( '50/50' );
		$search_mode_position = 'search-general_' . ( $submit_row['1-2 right'][0]['areaid'] ?? '' );

		foreach ( $collection->storage as $field ) {
			if ( $field->is_of_type( 'submit' ) ) {
				// If the search mode is missing, add it with an existing search field.
				$search_mode_position = $field->position;
			}
		}

		/** @var Search_Field[] $required_fields */
		$required_fields = [
			Search_Field_Submit::from_configuration(
				[
					'position'     => 'search-general_' . ( $submit_row['1-2 left'][0]['areaid'] ?? '' ),
					'search_clear' => (bool) ( $configuration['search_clear'] ?? false ),
				]
			),
			Search_Field_Search_Mode::from_configuration(
				[
					'position'   => $search_mode_position,
					'mode'       => $configuration['search_mode'] ?? 'any',
					'input_type' => 'hidden',
				]
			),
		];

		foreach ( $required_fields as $field ) {
			if ( ! $collection->has_fields_of_type( $field->get_type() ) ) {
				$collection->add( $field );
			}
		}

		return $collection;
	}

	/**
	 * Returns the fields as filtered template data.
	 *
	 * @since $ver$
	 *
	 * @return array The template data.
	 */
	public function to_template_data(): array {
		$search_fields = [];

		foreach ( $this->storage as $field ) {
			if ( ! $field->is_visible() ) {
				continue;
			}

			$search_fields[] = $field->to_template_data();
		}

		/**
		 * Modify what fields are shown. The order of the fields in the $search_filters array controls the order as displayed in the search bar widget.
		 *
		 * @param array                        $search_fields Array of search filters with `key`, `label`, `value`, `type`, `choices` keys
		 * @param GravityView_Widget_Search    $widget        Current widget object
		 * @param array  |null                 $widget_args   Args passed to this method. {@since 1.8}
		 * @param null|string|Template_Context $context       {@since 2.0}
		 * @param string|null                  $position      The search field position {@since $ver$}
		 *
		 * @type array The filtered search filters.
		 */
		$search_fields = apply_filters(
			'gravityview_widget_search_filters',
			$search_fields,
			$this->context['widget'] ?? null,
			$this->context['widget_args'] ?? null,
			$this->context['context'] ?? null,
			! empty( $field->position ) ? $field->position : null,
		);

		gravityview()->log->debug( 'Calculated Search Fields: ', [ 'data' => $search_fields ] );

		return $search_fields;
	}

	/**
	 * Returns a collection of fields of a specific type.
	 *
	 * @since $ver$
	 *
	 * @param string $type The search type or class name.
	 *
	 * @return self A new collection.
	 */
	public function by_type( string $type ): self {
		$clone = clone $this;

		$clone->storage = array_filter(
			$clone->storage,
			static function ( Search_Field $field ) use ( $type ): bool {
				if ( is_subclass_of( $type, Search_Field::class ) ) {
					return $field instanceof $type;
				}

				return $field->is_of_type( $type );
			}
		);

		return $clone;
	}
}
