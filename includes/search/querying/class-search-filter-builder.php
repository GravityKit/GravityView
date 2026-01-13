<?php

namespace GV\Search\Querying;

use GravityView_Search_WP_Widget;
use GravityView_Widget_Search;
use GV\View;
use JsonException;

/**
 * Converts a {@see Search_Request} into various filter options.
 *
 * @since $ver$
 */
final class Search_Filter_Builder {
	/**
	 * The singleton.
	 *
	 * @since $ver$
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Creates the instance.
	 *
	 * @since $ver$
	 */
	private function __construct() {
	}

	/**
	 * Returns the singleton.
	 *
	 * @since $ver$
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self( ...func_get_args() );
		}

		return self::$instance;
	}

	/**
	 * Transforms a Search Request into Gravity Forms Search Criteria.
	 *
	 * @since $ver$
	 *
	 * @param Search_Request $request The search request.
	 * @param View |null     $view    The View.
	 *
	 * @return array
	 */
	public function to_search_criteria( Search_Request $request, ?View $view = null ): array {
		$search_criteria   = [];
		$data              = $request->to_array();
		$searchable_fields = $this->get_searchable_fields( $view );

		$search_criteria['field_filters']['mode'] = $this->get_mode( $data );

		foreach ( $data['filters'] ?? [] as $filter ) {
			if ( ! in_array( $filter['key'], $searchable_fields, true ) ) {
				continue;
			}

			$this->handle_filter( $filter, $search_criteria, $view );
		}

		return $search_criteria;
	}

	/**
	 * Handles a single filter and adds it to the search criteria.
	 *
	 * @since $ver$
	 *
	 * @param array     $filter          The filter data.
	 * @param array     $search_criteria The search criteria to add to.
	 * @param View|null $view            The View.
	 */
	private function handle_filter( array $filter, array &$search_criteria, ?View $view = null ): void {
		$key = $filter['key'] ?? '';

		switch ( $key ) {
			case 'search_all':
				$this->handle_search_all( $filter, $search_criteria, $view );

				return;
			case 'entry_date':
				$this->handle_date_range( $filter, $search_criteria, $view );

				return;
		}

		$request_key = $filter['request_key'] ?? $key;
		$value       = $filter['value'] ?? '';
		$operator    = $filter['operator'] ?? '=';

		// Apply operator validation with request_key (BC) and key (new API).
		$operator = $this->get_operator( $operator, $request_key, $key );

		$search_criteria['field_filters'][] = [
			'key'      => 'entry_id' === $key ? 'id' : $key,
			'value'    => $value,
			'operator' => $operator,
		];
	}

	/**
	 * Returns the fields (or its keys) that are searchable for the provided View.
	 *
	 * @since $ver$
	 *
	 * @param View|null $view            The View.
	 * @param bool      $with_full_field Whether to return the entire field.
	 *
	 * @return string[]|array[]
	 */
	private function get_searchable_fields( ?View $view = null, bool $with_full_field = false ): array {
		if ( ! $view ) {
			return [];
		}

		$searchable_fields = array_merge(
			$this->get_searchable_fields_from_sidebar_widgets( $view ),
			$this->get_searchable_fields_from_gravityview_widgets( $view ),
		);

		if ( ! $with_full_field ) {
			$searchable_fields = array_column( $searchable_fields, 'field' );
			$searchable_fields = array_values( array_unique( $searchable_fields ) );
		}

		/**
		 * @since     2.5.1
		 * @depecated 2.14
		 */
		$searchable_fields = apply_filters_deprecated(
			'gravityview/search/searchable_fields/whitelist',
			[ $searchable_fields, $view, $with_full_field ],
			'2.14',
			'gravityview/search/searchable_fields/allowlist'
		);

		/**
		 * Modifies the fields able to be searched using the Search Bar
		 *
		 * @since  2.14
		 *
		 * @param array    $searchable_fields Array of GravityView-formatted fields or only the field ID? Example: [ '1.2', 'created_by' ]
		 * @param \GV\View $view              Object of View being searched.
		 * @param bool     $with_full_field   Does $searchable_fields contain the full field array or just field ID? Default: false (just field ID)
		 */
		$searchable_fields = apply_filters(
			'gravityview/search/searchable_fields/allowlist',
			$searchable_fields,
			$view,
			$with_full_field
		);

		return $searchable_fields;
	}

	/**
	 * Return searchable fields from {@see GravityView_Search_WP_Widget} instances.
	 *
	 * @since $ver$
	 *
	 * @param View $view The View.
	 *
	 * @return array The search fields.
	 */
	private function get_searchable_fields_from_sidebar_widgets( View $view ): array {
		$widgets = (array) get_option( 'widget_gravityview_search', [] );

		$searchable_fields = [];
		if ( ! $searchable_fields ) {
			return $searchable_fields;
		}

		foreach ( $widgets as $widget ) {
			if (
				empty( $widget['view_id'] )
				|| ( (int) $widget['view_id'] !== (int) $view->ID )
			) {
				continue;
			}

			$fields = $widget['search_fields'] ?? null;
			if ( ! $fields ) {
				continue;
			}

			if ( is_string( $fields ) ) {
				try {
					$fields = json_decode( $fields, true, 512, JSON_THROW_ON_ERROR );
				} catch ( JsonException $e ) {
					$fields = [];
				}
			}

			if ( ! is_array( $fields ) ) {
				continue;
			}

			foreach ( $fields as $field ) {
				if ( empty( $field['form_id'] ) ) {
					$field['form_id'] = $view->form ? $view->form->ID : 0;
				}
				$searchable_fields[] = $field;
			}
		}

		return $searchable_fields;
	}

	/**
	 * Return searchable fields from {@see GravityView_Widget_Search} instances.
	 *
	 * @since $ver$
	 *
	 * @param View $view The View.
	 *
	 * @return array The search fields.
	 */
	private function get_searchable_fields_from_gravityview_widgets( View $view ): array {
		$search_widget     = new GravityView_Widget_Search();
		$searchable_fields = [];

		foreach ( $view->widgets->by_id( $search_widget->get_widget_id() )->all() as $widget ) {
			if ( ! $widget instanceof GravityView_Widget_Search ) {
				continue;
			}

			foreach ( $widget->get_search_fields( $view ) as $field ) {
				if ( empty( $field['form_id'] ) ) {
					$field['form_id'] = $view->form ? $view->form->ID : 0;
				}
				$searchable_fields[] = $field;
			}
		}

		return $searchable_fields;
	}

	/**
	 * Retrieves the words in with its operator for querying.
	 *
	 * @since 2.21.1
	 *
	 * @param string $query       The search query.
	 * @param bool   $split_words Whether to split the words.
	 *
	 * @return array The search words with their operator.
	 */
	private function get_criteria_from_query( string $query, bool $split_words, bool $has_json_storage ): array {
		$words           = [];
		$quotation_marks = $this->get_quotation_marks();

		$regex = sprintf(
			'/(?<match>(\+|\-))?(%s)(?<word>.*?)(%s)/m',
			implode( '|', self::preg_quote( $quotation_marks['opening'] ?? [] ) ),
			implode( '|', self::preg_quote( $quotation_marks['closing'] ?? [] ) )
		);

		if ( preg_match_all( $regex, $query, $matches ) ) {
			$query = str_replace( $matches[0], '', $query );
			foreach ( $matches['word'] as $i => $value ) {
				$operator = '-' === $matches['match'][ $i ] ? 'not contains' : 'contains';
				$required = '+' === $matches['match'][ $i ];
				$words[]  = array_filter( compact( 'operator', 'value', 'required' ) );
			}
		}

		$values = [];
		if ( $query ) {
			$values = $split_words
				? preg_split( '/\s+/', $query )
				: [ preg_replace( '/\s+/', ' ', $query ) ];
		}

		foreach ( $values as $value ) {
			$is_exclude = '-' === ( $value[0] ?? '' );
			$required   = '+' === ( $value[0] ?? '' );
			$words[]    = array_filter(
				[
					'operator' => $is_exclude ? 'not contains' : 'contains',
					'value'    => ( $is_exclude || $required ) ? substr( $value, 1 ) : $value,
					'required' => $required,
				]
			);
		}

		// If one of the fields has a JSON storage, we add another criteria where the search value is escaped.
		if ( $has_json_storage ) {
			foreach ( $words as $i => $params ) {
				$original_value = $params['value'] ?? null;

				if ( ! is_string( $original_value ) ) {
					continue;
				}

				// This replicates the behavior of GF_Query_JSON_Literal::sql().
				$value = trim( wp_json_encode( $original_value ), '"' );
				$value = str_replace( '\\', '\\\\', $value );
				if ( $value !== $original_value ) {
					// We need to disable `required` on both the original and the copy, as both can't be true.
					unset( $words[ $i ]['required'], $params['required'] );

					$params['value'] = $value;
					$words[]         = $params;
				}
			}
		}

		// Include an empty key, for "all-fields search".
		array_walk(
			$words,
			static function ( &$word ) {
				$word['key'] = null;
			}
		);

		// Filter out empty words.
		return array_filter( $words, static fn( array $word ) => ! empty( $word['value'] ?? '' ) );
	}

	/**
	 * Returns a list of quotation marks.
	 *
	 * @since 2.21.1
	 *
	 * @return array List of quotation marks with `opening` and `closing` keys.
	 */
	private function get_quotation_marks(): array {
		$quotations_marks = [
			'opening' => [ '"', "'", '“', '‘', '«', '‹', '「', '『', '【', '〖', '〝', '〟', '｢' ],
			'closing' => [ '"', "'", '”', '’', '»', '›', '」', '』', '】', '〗', '〞', '〟', '｣' ],
		];

		/**
		 * Modify the quotation marks used to detect quoted searches.
		 *
		 * @since  2.22
		 *
		 * @param array $quotations_marks List of quotation marks with `opening` and `closing` keys.
		 */
		$quotations_marks = apply_filters( 'gk/gravityview/common/quotation-marks', $quotations_marks );

		return $quotations_marks;
	}

	/**
	 * Quotes values for a regex.
	 *
	 * @since 2.21.1
	 *
	 * @param array[] $words     The words to quote.
	 * @param string  $delimiter The delimiter.
	 *
	 * @return array[] The quoted words.
	 */
	private static function preg_quote( array $words, string $delimiter = '/' ): array {
		return array_map(
			static function ( string $mark ) use ( $delimiter ): string {
				return preg_quote( $mark, $delimiter );
			},
			$words
		);
	}

	/**
	 * Returns the validated operator for a specific key.
	 *
	 * Applies filter hooks twice when key differs from request_key:
	 * 1. First with request_key (BC for existing hooks using legacy keys like `gv_search`).
	 * 2. Then with key (new canonical names like `search_all`).
	 *
	 * @since $ver$
	 *
	 * @param string $operator    The provided operator.
	 * @param string $request_key The key as it appeared in the request (used in filter hooks for BC).
	 * @param string $key         The canonical key name.
	 * @param array  $allowed     The allowed operators.
	 * @param string $fallback    The fallback operator in case the provided isn't allowed.
	 *
	 * @return string The validated operator.
	 */
	private function get_operator(
		string $operator,
		string $request_key,
		string $key,
		array $allowed = [ '=' ],
		string $fallback = '='
	): string {
		// Apply with request_key first for backwards compatibility.
		$allowed = $this->apply_operator_allowlist_filter( $allowed, $request_key );

		// Apply with the canonical key if it is different from the request_key.
		if ( $key !== $request_key ) {
			$allowed = $this->apply_operator_allowlist_filter( $allowed, $key );
		}

		if ( ! in_array( $operator, $allowed, true ) ) {
			$operator = $fallback;
		}

		return $operator;
	}

	/**
	 * Applies the operator allowlist filter for a given key.
	 *
	 * @since $ver$
	 *
	 * @param array  $allowed The allowed operators.
	 * @param string $key     The key to filter by.
	 *
	 * @return array The filtered allowed operators.
	 */
	private function apply_operator_allowlist_filter( array $allowed, string $key ): array {
		/**
		 * @deprecated 2.14
		 */
		$allowed = apply_filters_deprecated(
			'gravityview/search/operator_whitelist',
			[ $allowed, $key ],
			'2.14',
			'gravityview/search/operator_allowlist'
		);

		/**
		 * Modifies an array of allowed operators for a field.
		 *
		 * @filter `gravityview/search/operator_allowlist`
		 *
		 * @since  2.14
		 *
		 * @param string[] $allowed An allowlist of operators.
		 * @param string   $key     The filter key (legacy request key or canonical key).
		 */
		return apply_filters( 'gravityview/search/operator_allowlist', $allowed, $key );
	}

	/**
	 * Returns whether whitespace should be stripped from a search value.
	 *
	 * @since $ver$
	 *
	 * @param View|null $view The View.
	 *
	 * @return bool
	 */
	private function is_whitespace_stripped( ?View $view ): bool {
		/**
		 * Whether to remove leading/trailing whitespaces from search value.
		 *
		 * @filter `gravityview/search-trim-input`
		 *
		 * @since  2.9.3
		 * @since  2.19.6 Added $view parameter
		 *
		 * @param bool $trim_search_value True: remove whitespace; False: keep as is [Default: true]
		 * @param View $view              The View being searched
		 */
		return apply_filters( 'gravityview/search-trim-input', true, $view );
	}

	/**
	 * Handles the search criteria for a `search_all` field.
	 *
	 * @since $ver$
	 *
	 * @param array     $filter          The filter object.
	 * @param array     $search_criteria The array to append the criteria to.
	 * @param View|null $view            The View.
	 */
	private function handle_search_all( array $filter, array &$search_criteria, ?View $view = null ): void {
		$value = $filter['value'] ?? '';
		if ( $this->is_whitespace_stripped( $view ) ) {
			$value = trim( $value );
		}

		$should_split_words = $this->should_split_words( $view );
		$has_json_storage   = $this->has_json_storage( $view );
		$criteria           = $this->get_criteria_from_query( $value, $should_split_words, $has_json_storage );

		foreach ( $criteria as $criterion ) {
			$search_criteria['field_filters'][] = $criterion;
		}
	}

	/**
	 * Handles an `entry_date` filter.
	 *
	 * @since $ver$
	 *
	 * @param array     $filter          The filter.
	 * @param array     $search_criteria The search criteria.
	 * @param View|null $view            The View.
	 */
	private function handle_date_range(
		array $filter,
		array &$search_criteria,
		?View $view = null
	): void {
		// Handle a single day filter: when type is 'day' and only start_date is set.
		if (
			( $filter['type'] ?? '' ) === 'day'
			&& isset( $filter['start_date'] )
			&& ! isset( $filter['end_date'] )
		) {
			$date      = $this->normalize_date( $filter['start_date'] );
			$timestamp = strtotime( $date );
			$date_only = gmdate( 'Y-m-d', $timestamp );

			$filter['end_date'] = $date_only . ' 23:59:59';
		}

		/**
		 * Whether to adjust the timezone for entries.
		 *
		 * `date_created` is stored in UTC format. Convert search date into UTC (also used on templates/fields/date_created.php).
		 * This is for backward compatibility before \GF_Query started to automatically apply the timezone offset.
		 *
		 * @since 1.12
		 *
		 * @param boolean $adjust_tz Use timezone-adjusted datetime? If true, adjusts date based on blog's timezone setting. If false, uses UTC setting. Default is `false`.
		 * @param string  $context   Where the filter is being called from. `search` in this case.
		 */
		$adjust_tz = apply_filters( 'gravityview_date_created_adjust_timezone', false, 'search' );

		$keys = [ 'start_date', 'end_date' ];
		foreach ( $keys as $key ) {
			if ( ! isset( $filter[ $key ] ) ) {
				continue;
			}

			$date = $this->normalize_date( $filter[ $key ] );

			if ( $view ) {
				$stored_date    = $view->settings->get( $key );
				$date_timestamp = strtotime( $date );
				if (
					( $stored_date && $date_timestamp )
					&& (
						// The stored dates are narrower than provided on the request.
						( 'start_date' === $key && $date_timestamp < strtotime( $stored_date ) )
						|| ( 'end_date' === $key && $date_timestamp > strtotime( $stored_date ) )
					)
				) {
					// Since the date is narrower, ignore this value.
					$date = null;
				}
			}

			if ( ! empty( $date ) ) {
				// Only append time if the original date doesn't already have one.
				if ( ! str_contains( $date, ':' ) ) {
					$date .= ' ' . ( 'start_date' === $key ? '00:00:00' : '23:59:59' );
				}

				if ( $adjust_tz ) {
					$date = get_gmt_from_date( $date );
				}

				$search_criteria[ $key ] = $date;
			}
		}
	}

	/**
	 * Normalize date from a datepicker format to Y-m-d format.
	 *
	 * @since 2.42
	 *
	 * @param string $date_string The date string to normalize.
	 *
	 * @return string Normalized date string or empty string if invalid.
	 */
	private function normalize_date( string $date_string ): string {
		if ( empty( $date_string ) ) {
			return '';
		}

		// Date string is already in the proper format.
		$date = date_create_from_format( 'Y-m-d H:i:s', $date_string );
		if ( $date ) {
			return $date_string;
		}

		$date = date_create_from_format( $this->get_datepicker_format( true ), $date_string );
		if ( $date ) {
			return $date->format( 'Y-m-d' );
		}

		return '';
	}

	/**
	 * Retrieve the datepicker format.
	 *
	 * @see https://docs.gravitykit.com/article/115-changing-the-format-of-the-search-widgets-date-picker
	 *
	 * @param bool $date_format Whether to return the PHP date format or the datpicker class name. Default: false.
	 *
	 * @return string The datepicker format placeholder, or the PHP date format.
	 */
	private function get_datepicker_format( bool $date_format = false ): string {
		$default_format = 'mdy';

		/**
		 * @filter `gravityview/widgets/search/datepicker/format`
		 * @since  2.1.1
		 *
		 * @param string $format Default: mdy
		 *                       Options are:
		 *                       - `mdy` (mm/dd/yyyy)
		 *                       - `dmy` (dd/mm/yyyy)
		 *                       - `dmy_dash` (dd-mm-yyyy)
		 *                       - `dmy_dot` (dd.mm.yyyy)
		 *                       - `ymd_slash` (yyyy/mm/dd)
		 *                       - `ymd_dash` (yyyy-mm-dd)
		 *                       - `ymd_dot` (yyyy.mm.dd)
		 */
		$format = apply_filters( 'gravityview/widgets/search/datepicker/format', $default_format );

		$gf_date_formats = [
			'mdy'       => 'm/d/Y',
			'dmy_dash'  => 'd-m-Y',
			'dmy_dot'   => 'd.m.Y',
			'dmy'       => 'd/m/Y',
			'ymd_slash' => 'Y/m/d',
			'ymd_dash'  => 'Y-m-d',
			'ymd_dot'   => 'Y.m.d',
		];

		if ( ! $date_format ) {
			// If the format key isn't valid, return the default format key.
			return isset( $gf_date_formats[ $format ] ) ? $format : $default_format;
		}

		// If the format key isn't valid, return the default format value.
		return $gf_date_formats[ $format ] ?? $gf_date_formats[ $default_format ];
	}

	/**
	 * Returns whether words should be split.
	 *
	 * @since $ver$
	 *
	 * @param View|null $view The View.
	 *
	 * @return bool Whether words should be split.
	 */
	private function should_split_words( ?View $view = null ): bool {
		/**
		 * Search for each word separately or the whole phrase?
		 *
		 * @since  1.20.2
		 * @since  2.19.6 Added $view parameter
		 *
		 * @param bool      $split_words True: split a phrase into words; False: search whole word only [Default: true]
		 * @param View|null $view        The View being searched
		 */
		return apply_filters( 'gravityview/search-all-split-words', true, $view );
	}

	/**
	 * Returns whether one of the fields is stored as JSON.
	 *
	 * @since $ver$
	 *
	 * @param View|null $view The View.
	 *
	 * @return bool Whether the View contains JSON storage.
	 */
	private function has_json_storage( ?View $view = null ): bool {
		if ( ! $view ) {
			return false;
		}

		$fields = $view->form->form['fields'] ?? [];
		foreach ( $fields as $field ) {
			if ( 'json' === ( $field['storageType'] ?? null ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Retrieves the search mode from the provided data array.
	 *
	 * @since $ver$
	 *
	 * @param array $data The data array containing search parameters.
	 *
	 * @return "any"|"all" The search mode.
	 */
	private function get_mode( array $data ): string {
		/**
		 * @filter `gravityview/search/mode` Modifies the search mode.
		 *
		 * @since  1.5.1
		 *
		 * @param string $mode Search mode (`any` vs `all`).
		 */
		$mode = strtolower( apply_filters( 'gravityview/search/mode', $data['mode'] ?? 'any' ) );

		return in_array( $mode, [ 'any', 'all' ], true ) ? $mode : 'any';
	}
}
