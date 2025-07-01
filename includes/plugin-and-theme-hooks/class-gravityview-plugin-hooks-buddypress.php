<?php
/**
 * Improve compatibility with BuddyPress and BuddyBoss.
 *
 * @file      class-gravityview-plugin-hooks-buddypress.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2025, Katz Web Services, Inc.
 *
 * @since TODO
 */

/**
 * @inheritDoc
 */
class GravityView_Plugin_Hooks_BuddyPress extends GravityView_Plugin_and_Theme_Hooks {

	use GravityView_Permalink_Override_Trait;

	/**
	 * The function name to check if we are on a BuddyPress/Boss page.
	 *
	 * @since TODO
	 *
	 * @var string
	 */
	protected $function_name = 'bp_is_user_profile';

	/**
	 * Remove the permalink structure for BuddyPress pages.
	 *
	 * @since TODO
	 *
	 * @return bool Whether to remove the permalink structure from View rendered links.
	 */
	protected function should_disable_permalink_structure() {

		if ( ! bp_is_user_profile() ) {
			return false;
		}

		return true;
	}
}

new GravityView_Plugin_Hooks_BuddyPress();
