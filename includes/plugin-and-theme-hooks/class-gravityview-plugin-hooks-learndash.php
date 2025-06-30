<?php
/**
 * Add GravityView integration to LifterLMS.
 *
 * @file      class-gravityview-plugin-hooks-lifterlms.php
 * @since     2.20
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      https://gravityview.co
 * @copyright Copyright 2024, Katz Web Services, Inc.
 *
 * @package   GravityView
 */

final class GravityView_Plugin_Hooks_LearnDash extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * The constant name for the LearnDash version.
	 *
	 * @var string
	 */
	protected $constant_name = 'LEARNDASH_VERSION';

	/**
	 * The function name to fetch LearnDash post types.
	 *
	 * @var string
	 */
	protected $function_name = 'learndash_get_post_types';

	/**
	 * Remove the permalink structure for LearnDash post types.
	 *
	 * @since TODO
	 *
	 * @return bool Whether to remove the permalink structure from View rendered links.
	 */
	public function should_disable_permalink_structure() {
		// The current page is not a LearnDash post type.
		if ( ! in_array( get_post_type(), learndash_get_post_types(), true ) ) {
			return parent::should_disable_permalink_structure();
		}

		return true;
	}
}

new GravityView_Plugin_Hooks_LearnDash();
