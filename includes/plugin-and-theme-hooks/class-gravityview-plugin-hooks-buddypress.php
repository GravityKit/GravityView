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
 * @since 2.41
 */

/**
 * @inheritDoc
 */
class GravityView_Plugin_Hooks_BuddyPress extends GravityView_Plugin_and_Theme_Hooks {

	use GravityView_Permalink_Override_Trait;

	/**
	 * The function name to check if we are on a BuddyPress/Boss page.
	 *
	 * @since 2.41
	 *
	 * @var string
	 */
	protected $function_name = 'is_buddypress';

	/**
	 * Remove the permalink structure for BuddyPress pages.
	 *
	 * @since 2.41
	 *
	 * @return bool Whether to remove the permalink structure from View rendered links.
	 */
	protected function should_disable_permalink_structure() {
		return is_buddypress();
	}
}

new GravityView_Plugin_Hooks_BuddyPress();
