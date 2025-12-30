<?php
/**
 * Add Jetpack plugin compatibility to GravityView.
 *
 * Prevents Jetpack Comments from hijacking GravityView Single Entry pages
 * when the Ratings & Reviews extension is enabled.
 *
 * @file      class-gravityview-plugin-hooks-jetpack.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2024, Katz Web Services, Inc.
 *
 * @since 2.29
 */

/**
 * Jetpack plugin compatibility for GravityView.
 *
 * Jetpack Comments replaces the native WordPress comment form with an iframe.
 * When Ratings & Reviews enables comments on GravityView entries, Jetpack
 * intercepts and breaks the single entry display. This class disables Jetpack
 * Comments specifically on GravityView entry pages.
 *
 * @since 2.29
 */
class GravityView_Plugin_Hooks_Jetpack extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * Class name to check for Jetpack plugin availability.
	 *
	 * @since 2.29
	 *
	 * @var string
	 */
	protected $class_name = 'Jetpack';

	/**
	 * Default entry endpoint name used when GravityView classes aren't loaded.
	 *
	 * @see \GV\Entry::get_endpoint_name() The canonical source for this value.
	 *
	 * @since 2.29
	 *
	 * @var string
	 */
	const DEFAULT_ENTRY_ENDPOINT = 'entry';

	/**
	 * Conditionally disable Jetpack Comments module on GravityView entry pages.
	 *
	 * This method must be called as early as possible (before Jetpack initializes)
	 * to prevent the Comments module from loading. It's registered at file load time
	 * rather than in add_hooks() to ensure it runs before Jetpack reads its modules list.
	 *
	 * @since 2.29
	 *
	 * @param mixed $modules Array of active Jetpack module slugs, or non-array on error.
	 *
	 * @return mixed Modified modules array with 'comments' removed if on entry page, or original value.
	 */
	public static function maybe_disable_jetpack_comments( $modules ) {
		// Graceful failure: return unchanged if not an array.
		if ( ! is_array( $modules ) ) {
			return $modules;
		}

		// Only modify modules list if we're on a GravityView entry page.
		if ( ! self::is_gravityview_entry_request() ) {
			return $modules;
		}

		// Remove 'comments' module and reindex array to maintain Jetpack compatibility.
		return array_values( array_diff( $modules, array( 'comments' ) ) );
	}

	/**
	 * Determine if the current request is for a GravityView single entry page.
	 *
	 * Uses URL-based detection since this runs very early in the WordPress lifecycle,
	 * before query vars and other WordPress APIs are available. This is intentionally
	 * simple to avoid false negatives that could break Jetpack Comments on regular pages.
	 *
	 * @since 2.29
	 *
	 * @return bool True if this appears to be a GravityView entry request.
	 */
	private static function is_gravityview_entry_request() {
		$endpoint = self::get_entry_endpoint_name();

		// Check URL path for /{endpoint}/{entry_id_or_slug}/ pattern (pretty permalinks).
		if ( self::uri_contains_entry_endpoint( $endpoint ) ) {
			return true;
		}

		// Check for ?{endpoint}=value query parameter (non-pretty permalinks).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check, no action taken on value.
		if ( isset( $_GET[ $endpoint ] ) && '' !== $_GET[ $endpoint ] ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the request URI contains the entry endpoint pattern.
	 *
	 * @since 2.29
	 *
	 * @param string $endpoint The entry endpoint name to search for.
	 *
	 * @return bool True if the URI contains the entry endpoint pattern.
	 */
	private static function uri_contains_entry_endpoint( $endpoint ) {
		// Graceful failure: no REQUEST_URI means we can't check.
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		// Graceful failure: invalid endpoint name.
		if ( empty( $endpoint ) || ! is_string( $endpoint ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only used for pattern matching, not output.
		$uri = $_SERVER['REQUEST_URI'];

		// Match /{endpoint}/{entry_id_or_slug}/ pattern.
		// Supports: numeric IDs, alphanumeric slugs, slugs with hyphens/underscores.
		$pattern = '#/' . preg_quote( $endpoint, '#' ) . '/[a-zA-Z0-9_-]+/?#';

		return (bool) preg_match( $pattern, $uri );
	}

	/**
	 * Get the GravityView entry endpoint name.
	 *
	 * Attempts to retrieve the configured endpoint name from GravityView.
	 * Falls back to default 'entry' if GravityView classes aren't loaded yet,
	 * which is expected when this runs early in the WordPress lifecycle.
	 *
	 * @since 2.29
	 *
	 * @return string The entry endpoint name. Never empty.
	 */
	private static function get_entry_endpoint_name() {
		// Try to get the endpoint from GravityView if available.
		if ( class_exists( '\GV\Entry' ) && method_exists( '\GV\Entry', 'get_endpoint_name' ) ) {
			$endpoint = \GV\Entry::get_endpoint_name();

			// Graceful failure: ensure we got a valid string back.
			if ( ! empty( $endpoint ) && is_string( $endpoint ) ) {
				return $endpoint;
			}
		}

		return self::DEFAULT_ENTRY_ENDPOINT;
	}
}

/*
 * Register Jetpack compatibility filters immediately when this file loads.
 *
 * IMPORTANT: These filters must be added at file load time, not in add_hooks().
 *
 * Jetpack checks its active modules early in the WordPress lifecycle. By the time
 * add_hooks() runs (on 'wp_loaded'), Jetpack has already loaded its Comments module.
 * Registering these filters here ensures they run before Jetpack initializes.
 *
 * @see \GravityView_Plugin_and_Theme_Hooks::add_hooks() Runs on 'wp_loaded' - too late for Jetpack.
 * @see Automattic\Jetpack\Status\Modules::get_active() Where Jetpack reads active modules.
 */
add_filter( 'jetpack_active_modules', array( 'GravityView_Plugin_Hooks_Jetpack', 'maybe_disable_jetpack_comments' ), 0 );
add_filter( 'option_jetpack_active_modules', array( 'GravityView_Plugin_Hooks_Jetpack', 'maybe_disable_jetpack_comments' ), 0 );

new GravityView_Plugin_Hooks_Jetpack();
