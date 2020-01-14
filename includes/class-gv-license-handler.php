<?php
/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * @deprecated 2.0 Use \GV\License_Handler instead.
 */
class GV_License_Handler extends \GV\License_Handler {
	/**
	 * @param \GV\Addon_Settings $GFAddOn
	 *
	 * @deprecated 2.0 Use \GV\License_Handler::get instead
	 *
	 * @return GV\License_Handler
	 */
	public static function get_instance( $settings ) {
		_deprecated_function( __METHOD__, '2.0', '\GV\License_Handler::get()' );
		return \GV\License_Handler::get( $settings );
	}
}
