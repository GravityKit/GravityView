<?php

/**
 * GravityView Settings class (get/set) using the Gravity Forms App framework
 *
 * @since 1.7.4 (Before, used the Redux Framework)
 * @deprecated Use gravityview()->plugin->settings
 */
class GravityView_Settings extends \GV\Plugin_Settings {
	/**
	 * @deprecated Use gravityview()->plugin->settings
	 * @return \GV\Settings
	 */
	public function __wakeup() {}
	public function __clone() {}

	/**
	 * @deprecated Use gravityview()->plugin->settings
	 * @return \GV\Plugin_Settings
	 */
	public static function get_instance() {
		gravityview()->log->warning( '\GravityView_Settings is deprecated. Use gravityview()->plugin->settings instead.' );
		return gravityview()->plugin->settings;
	}
}
