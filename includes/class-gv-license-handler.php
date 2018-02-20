<?php
/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * @deprecated Use \GV\License_Handler instead.
 */
class GV_License_Handler extends \GV\License_Handler {
	/**
	 * @param \GV\Addon_Settings $GFAddOn
	 *
	 * @deprecated Use \GV\License_Handler::get instead
	 *
	 * @return GV\License_Handler
	 */
	public static function get_instance( $settings ) {
		gravityview()->log->warning( 'GV_License_Handler::get_instance() is deprated in favor of \GV\License_Handler::get()' );
		return \GV\License_Handler::get( $settings );
	}
}
