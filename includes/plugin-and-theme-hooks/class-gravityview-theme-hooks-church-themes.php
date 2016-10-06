<?php
/**
 * Add Church Themes compatibility to GravityView
 *
 * @file      class-gravityview-theme-hooks-church-themes.php
 * @package   GravityView
 * @license   GPL2
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2016, Katz Web Services, Inc.
 *
 * @since 1.17
 */

/**
 * @inheritDoc
 * @since 1.17
 */
class GravityView_Theme_Hooks_Church_Themes extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * Church Themes framework version number
	 *
	 * @inheritDoc
	 * @since 1.17
	 */
	protected $constant_name = 'CTFW_VERSION';

	/**
	 * Add filters
	 *
	 * @since 1.17
	 *
	 * @return void
	 */
	protected function add_hooks() {
		parent::add_hooks();

		add_filter( 'ctfw_has_content', array( $this, 'if_gravityview_return_true' ) );
	}

	/**
	 * Tell Church Themes that GravityView has content if the current page is a GV post type or has shortcode
	 *
	 * @since 1.17
	 *
	 * @param bool $has_content Does the post have content?
	 *
	 * @return bool True: It is GV post type, or has shortcode, or $has_content was true.
	 */
	public function if_gravityview_return_true( $has_content = false ) {

		if( ! class_exists( 'GravityView_frontend' ) ) {
			return $has_content;
		}

		$instance = GravityView_frontend::getInstance();

		return ( $instance->is_gravityview_post_type || $instance->post_has_shortcode ) ? true : $has_content;
	}
}

new GravityView_Theme_Hooks_Church_Themes;