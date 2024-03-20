<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 */

/**
 * Delete GravityView content when GravityView is uninstalled, if the setting is set to "Delete on Uninstall"
 *
 * @deprecated Use gravityview()->plugin->uninstall() instead.
 *
 * @since 1.15
 */
class GravityView_Uninstall {

	/**
	 * Delete GravityView Views, settings, roles, caps, etc.
	 *
	 * @see https://youtu.be/FXy_DO6IZOA?t=35s
	 * @since 1.15
	 *
	 * @deprecated Use gravityview()->plugin->uninstall()
	 *
	 * @return void
	 */
	public function fire_everything() {
		gravityview()->log->warning( '\GravityView_Uninstall::fire_everything is deprecated. Use \GV\Plugin::uninstall instead' );
		gravityview()->plugin->uninstall();
	}
}
