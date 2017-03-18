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
