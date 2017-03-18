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
}
