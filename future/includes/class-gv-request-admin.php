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
		if ( ! ( $args = func_get_args() ) || count( $args ) != 2 ) {
			return true;
		}

		$context = $args[1];

		/**
		 * Assume false by default.
		 */
		$is_page = false;

		if ( function_exists( '\get_current_screen' ) || function_exists( 'get_current_screen' ) ) {
			if ( ( $current_screen = \get_current_screen() ) && $current_screen->post_type == 'gravityview' ) {
				if ( $is_gv_edit_list = $current_screen->base == 'edit' ) {
					$is_page = 'views';
				} else if ( $is_gv_edit_single = $current_screen->base == 'post' ) {
					$is_page = 'single';
				} else if ( $is_gv_settings = $current_screen->id == 'gravityview_page_gravityview_settings' ) {
					$is_page = 'settings';
				}
			}
		}

		/**
		 * @filter `gravityview_is_admin_page` Is the current admin page a GravityView-related page?
		 * @param[in,out] string|bool $is_page If false, no. If string, the name of the page (`single`, `settings`, or `views`)
		 * @param[in] string $hook The name of the page to check against. Is passed to the method.
		 */
		$is_page = apply_filters( 'gravityview_is_admin_page', $is_page, $args[0] );

		// If the current page is the same as the compared page
		if ( ! empty( $context ) ) {
			return $is_page === $context;
		}

		return $is_page;
	}
}
