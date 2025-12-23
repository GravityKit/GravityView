<?php

namespace GV\Search\Querying\Request;

use GV\Admin_Request;
use GV\CLI_Request;
use GV\Mock_Request;
use GV\Request;

/**
 * Represents a search request, which can be created in multiple ways.
 *
 * @since $ver$
 */
final class Search_Request {
	/**
	 * Micro cache for the meta regex part.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	private static string $meta_regex;

	/**
	 * Holds the search arguments.
	 *
	 * @since $ver$
	 *
	 * @var array
	 */
	private array $arguments = [];

	/**
	 * The search mode.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	private string $mode = 'any';

	/**
	 * Creates the instance.
	 *
	 * @since $ver$
	 */
	private function __construct() {
	}

	/**
	 * Creates a search request from the legacy `$_REQUEST` structure.
	 *
	 * @param Request $request The request object.
	 *
	 * @return self
	 */
	public static function from_request( Request $request ): ?self {
		$request_arguments = self::get_request_arguments( $request );

		return self::from_arguments( $request_arguments );
	}

	/**
	 * Returns whether the request contains search request markers.
	 *
	 * @since $ver$
	 *
	 * @param Request $request The request object.
	 *
	 * @return bool Whether the request is a search request.
	 */
	public static function is_search_request( Request $request ): bool {
		if ( $request instanceof Admin_Request ) {
			return false;
		}

		$request_arguments = self::get_request_arguments( $request );
		$search_arguments  = self::get_search_arguments( $request_arguments );

		return [] !== $search_arguments;
	}

	/**
	 * Creates a search request object from arguments.
	 *
	 * @since $ver$
	 *
	 * @param array $arguments The request arguments.
	 *
	 * @return self|null The search request object.
	 */
	public static function from_arguments( array $arguments ): ?self {
		$search_arguments = self::get_search_arguments( $arguments );
		if ( [] === $search_arguments ) {
			return null;
		}

		$instance = new self();

		$instance->arguments = $search_arguments;
		$instance->set_mode( $arguments );

		return $instance;
	}

	/**
	 * Returns the provided arguments on the current request.
	 *
	 * @since $ver$
	 *
	 * @param Request $request The request object.
	 *
	 * @return array The arguments of this request.
	 */
	private static function get_request_arguments( Request $request ): array {
		if (
			$request instanceof CLI_Request
			|| $request instanceof Mock_Request
		) {
			$arguments = $request->get_arguments();
		} else {
			$search_method = strtolower( apply_filters( 'gravityview/search/method', 'get' ) );
			//phpcs:ignore
			$arguments = 'post' === $search_method ? $_POST : $_GET;
		}

		$arguments = stripslashes_deep( $arguments );
		if ( ! is_null( $arguments ) ) {
			$arguments = gv_map_deep( $arguments, 'rawurldecode' );
		}

		return array_filter( $arguments, static fn( $value ) => '' !== $value );
	}

	/**
	 * Returns whether the request has a GV field key.
	 *
	 * @since 2.0.7
	 *
	 * @param array $arguments the request arguments.
	 *
	 * @return array The search arguments.
	 */
	private static function get_search_arguments( array $arguments ): array {
		$search_keys = [ 'gv_search', 'gv_start', 'gv_end', 'gv_by', 'gv_id' ];
		$meta_regex  = self::get_meta_field_regex();

		$search_field_regex = '/^(filter|input)_(([0-9_]+)|' . $meta_regex . ')$/sm';

		$search_arguments = [];
		foreach ( $arguments as $key => $value ) {
			// Ensure a string key.
			$key = (string) $key;

			if ( in_array( $key, $search_keys, true ) ) {
				$search_arguments[ $key ] = [ 'value' => $value ];
				continue;
			}

			if ( preg_match( $search_field_regex, $key ) ) {
				$value = str_replace( [ 'filter_', 'input_' ], [ '', '' ], $value );
				if ( preg_match( '/^[0-9_]+$/', $value ) ) {
					$value = str_replace( '_', '.', $value );
				}

				$search_arguments[ $key ] = [
					'value' => $value,
				];
			}
		}

		foreach ( $search_arguments as $key => $tuple ) {
			$operator = ( $arguments[ $key . '|op' ] ?? null );
			if ( ! $operator ) {
				$operator = 'gv_search' === $key ? 'contains' : '=';
			}
			$search_arguments[ $key ]['operator'] = $operator;
		}

		return $search_arguments;
	}

	/**
	 * Calculates (and caches) the meta-fields regex part.
	 *
	 * @since $ver$
	 *
	 * @return string The regex part.
	 */
	private static function get_meta_field_regex(): string {
		if ( isset( self::$meta_regex ) ) {
			return self::$meta_regex;
		}

		$gv_fields = \GravityView_Fields::get_all();

		$meta = [];
		foreach ( $gv_fields as $field ) {
			if ( ! empty( $field->_gf_field_class_name ) ) {
				continue;
			}

			$meta[] = preg_quote( $field->name, '/' );
		}

		self::$meta_regex = implode( '|', $meta );

		return self::$meta_regex;
	}

	/**
	 * Convert the request to an array object.
	 *
	 * @since $ver$
	 *
	 * @return array{mode: string, filters: array{key: string, operator:string, value:mixed } The array.
	 */
	public function to_array(): array {
		$filters = [];

		foreach ( $this->arguments as $key => $data ) {
			if ( 'gv_search' === $key ) {
				$filters[] = [
					'key'      => 'search_all',
					'operator' => $data['operator'] ?? 'contains',
					'value'    => $data['value'],
				];
				continue;
			}
			if ( 'gv_id' === $key ) {
				$filters[] = [
					'key'      => 'entry_id',
					'operator' => $data['operator'] ?? '=',
					'value'    => absint( $data['value'] ?? 0 ),
				];
				continue;
			}

			if ( 'gv_by' === $key ) {
				$filters[] = [
					'key'      => 'created_by',
					'operator' => $data['operator'] ?? '=',
					'value'    => $data['value'] ?? '',
				];
				continue;
			}

			if ( in_array( $key, [ 'gv_start', 'gv_end' ], true ) ) {
				$filters[] = [
					'key'      => 'gv_start' === $key ? 'start_date' : 'end_date',
					'operator' => '=',
					'value'    => $data['value'] ?? '',
				];
				continue;
			}

			// Default.
			$filters[] = [
				'key'      => $key,
				'operator' => $data['operator'] ?? '=',
				'value'    => $data['value'] ?? '',
			];
		}

		return [
			'mode'    => $this->mode,
			'filters' => $filters,
		];
	}

	/**
	 * Set the search mode.
	 *
	 * @since $ver$
	 *
	 * @param array $arguments The request arguments.
	 */
	private function set_mode( array $arguments ): void {
		$mode = 'any';
		if ( isset( $arguments['gv_mode'] ) ) {
			$mode = strtolower( $arguments['gv_mode'] );
		}

		if ( ! in_array( $mode, [ 'all', 'any' ], true ) ) {
			$mode = 'any';
		}

		$this->mode = $mode;
	}
}
