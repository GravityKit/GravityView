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
	 * Use the permalink override trait. Alias it so that we can call the trait's method first.
	 *
	 * @since TODO
	 *
	 * @var GravityView_Permalink_Override_Trait
	 */
	use GravityView_Permalink_Override_Trait {
		GravityView_Permalink_Override_Trait::on_template_redirect as trait_on_template_redirect;
	}

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
	 * Check if the current post type is a LearnDash post type.
	 *
	 * @since TODO
	 *
	 * @return bool Whether the current post type is a LearnDash post type.
	 */
	private function is_learndash_post_type() {
		return in_array( get_post_type(), learndash_get_post_types(), true );
	}

	/**
	 * Remove the permalink structure for LearnDash post types.
	 *
	 * @since TODO
	 *
	 * @return bool Whether to remove the permalink structure from View rendered links.
	 */
	protected function should_disable_permalink_structure() {
		// The current page is not a LearnDash post type.
		if ( ! $this->is_learndash_post_type() ) {
			return false;
		}

		return true;
	}

	/**
	 * Handle template redirect for LearnDash integration.
	 *
	 * Extends the trait functionality to also remove the single entry title filter.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	public function on_template_redirect() {

		// Call the trait's method first.
		$this->trait_on_template_redirect();

		// Add LearnDash-specific logic.
		if ( ! $this->is_learndash_post_type() ) {
			return;
		}

		// Don't change the title of the single entry page for LearnDash posts.
		if ( gravityview()->request->is_entry() ) {
			remove_filter( 'the_title', array( GravityView_frontend::getInstance(), 'single_entry_title' ), 1, 2 );
		}
	}
}

new GravityView_Plugin_Hooks_LearnDash();
