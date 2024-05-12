<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * Filtering window settings:
 *
 * Offset and limit, pagination.
 */
class Entry_Offset {

	/** @var int The offset. */
	public $offset = 0;

	/** @var int The limit. */
	public $limit = 20;

	/**
	 * Return a search_criteria format for this offset.
	 *
	 * @param int $page The page. Default: 1
	 *
	 * @return array ['page_size' => N, 'offset' => N]
	 */
	public function to_paging( $page = 1 ) {
		return array(
			'page_size' => $this->limit,
			'offset'    => ( ( $page - 1 ) * $this->limit ) + $this->offset,
		);
	}
}
