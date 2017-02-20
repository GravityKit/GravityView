<?php
namespace GV\Shortcodes;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The base \GV\Shortcode class.
 *
 * Contains some unitility methods, base class for all GV Shortcodes.
 */
class gravityview extends \GV\Shortcode {
	/**
	 * {@inheritDoc}
	 */
	public $name = 'gravityview';
}
