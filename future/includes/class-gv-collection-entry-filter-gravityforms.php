<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * Entry filtering settings Gravity Forms style.
 *
 * The good old $search_criteria in object form.
 */
class GF_Entry_Filter extends Entry_Filter {

	/**
	 * @var array the $search_criteria for Gravity Forms
	 */
	private $search_criteria = array();

	/**
	 * Creater a filter from this criteria.
	 *
	 * @param array $search_criteria The Gravity Forms search criteria.
	 * @see GFAPI::search_entries
	 *
	 * @return \GV\GF_Entry_Filter The filter.
	 */
	public static function from_search_criteria( $search_criteria ) {
		$filter = new self();
		$filter->search_criteria = $search_criteria;
		return $filter;
	}

	/**
	 * Merge two search criteria arrays.
	 *
	 * If two values collide, $b always wins.
	 *
	 * @param array $a One Gravity Forms search criteria.
	 * @param array $a Another Gravity Forms search criteria.
	 *
	 * @see GFAPI::search_entries
	 *
	 * @return array Merged search criteria.
	 */
	public static function merge_search_criteria( $a, $b ) {
		$search_criteria = array();

		foreach ( array( 'field_filters', 'start_date', 'end_date', 'status' ) as $key ) {
			switch ( $key ):
				case 'field_filters':
					$field_filters = array_merge( empty( $a[ $key ] ) ? array() : $a[ $key ], empty( $b[ $key ] ) ? array() : $b[ $key ] );
					if ( ! empty( $field_filters ) ) {
						$search_criteria[ $key ] = $field_filters;
					}

					if ( ! empty( $b[ $key ]['mode'] ) ) {
						$search_criteria[ $key ]['mode' ] = $b[ $key ]['mode'];
					} else if ( ! empty( $a[ $key ]['mode'] ) ) {
						$search_criteria[ $key ]['mode' ] = $a[ $key ]['mode'];
					}
					break;
				case 'start_date':
				case 'end_date':
				case 'status':
					if ( isset( $b[ $key ] ) ) {
						$search_criteria[ $key ] = $b[ $key ];
					} else if ( isset( $a[ $key ] ) ) {
						$search_criteria[ $key ] = $a[ $key ];
					}
					break;
			endswitch;
		}

		return $search_criteria;
	}

	/**
	 * Get the $search_criteria.
	 *
	 * @see GFAPI::search_entries
	 *
	 * @return array $search_criteria The Gravity Forms search criteria.
	 */
	public function as_search_criteria() {
		return $this->search_criteria;
	}
}
