<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The default Dashboard Request class.
 */
class Admin_Request extends Request {
	/**
	 * Check if WordPress is_admin(), and whether we have a GravityView page.
	 *
	 * @return bool If is in an admin context or not.
	 *
	 * Accepts optional $hook and $context arguments.
	 * @return bool|string If `false`, not a GravityView page. `true` if $page is passed and is the same as current page. Otherwise, the name of the page (`single`, `settings`, or `views`)
	 */
	public static function is_admin() {
		if ( ! parent::is_admin() ) {
			return false;
		}

		/**
		 * Regular check.
		 */
		if ( ! ( $args = func_get_args() ) ) {
			return true;
		}

		$hook    = \GV\Utils::get( $args, 0, '' );
		$context = \GV\Utils::get( $args, 1, null );

		/**
		 * Assume false by default.
		 */
		$is_page = false;

		if( function_exists( '\get_current_screen' ) || function_exists( 'get_current_screen' ) ) {
			$current_screen = \get_current_screen();
		} else {
			$current_screen = false;
		}

		if ( $current_screen && $current_screen->post_type == 'gravityview' ) {
			if ( $is_gv_edit_list = 'edit' === $current_screen->base ) {
				$is_page = 'views';
			} elseif ( $is_gv_edit_single = 'post' === $current_screen->base ) {
				$is_page = 'single';
			} elseif ( $is_gv_settings = 'gravityview_page_gravityview_settings' === $current_screen->id ) {
				$is_page = 'settings';
			} elseif( $is_extensions = 'gravityview_page_gv-admin-installer' === $current_screen->id ) {
				$is_page = 'downloads';
			} elseif( $is_changelog = 'gravityview_page_gv-changelog' === $current_screen->id ) {
				$is_page = 'changelog';
			} elseif( $is_getting_started = 'gravityview_page_gv-getting-started' === $current_screen->id ) {
				$is_page = 'getting-started';
			} elseif( $is_credits = 'gravityview_page_gv-credits' === $current_screen->id ) {
				$is_page = 'credits';
			}
		}

		/**
		 * @filter `gravityview_is_admin_page` Is the current admin page a GravityView-related page?
		 * @param[in,out] string|bool $is_page If false, no. If string, the name of the page (`single`, `settings`, or `views`)
		 * @param[in] string $hook The name of the page to check against. Is passed to the method.
		 */
		$is_page = apply_filters( 'gravityview_is_admin_page', $is_page, $hook );

		// If the current page is the same as the compared page
		if ( ! empty( $context ) ) {
			return $is_page === $context;
		}

		return $is_page;
	}
}
