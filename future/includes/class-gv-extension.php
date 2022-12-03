<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The \GV\Extension class.
 *
 * An interface that most extensions would want to adhere to and inherit from.
 *
 * @deprecated 2.16.1
 * @TODO Remove once all extensions have been updated to use Foundation.
 */
abstract class Extension {}
