<?php

if ( ! class_exists( '\GV\Addon_Settings' ) ) {
	return;
}

/**
 * GravityView Settings class (get/set/license validation) using the Gravity Forms App framework
 * @since 1.7.4 (Before, used the Redux Framework)
 * @deprecated Use gravityview()->plugin->settings
 */
class GravityView_Settings extends \GV\Addon_Settings {
	/**
	 * @deprecated Use gravityview()->plugin->settings
	 * @return \GV\Global_Settings
	 */
	private function __wakeup() {}
	private function __clone() {}

	/**
	 * @deprecated Use gravityview()->plugin->settings
	 * @return \GV\Addon_Settings
	 */
	public static function get_instance() {
		gravityview()->log->warning( '\GravityView_Settings is deprecated. Use gravityview()->plugin->settings instead.' );
		return gravityview()->plugin->settings;
	}
}
