<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) )
	die();

/**
 * The base Entry class.
 *
 * Contains all entry data and some processing and logic rules.
 */
class Entry {
	
	/**
	 * Adds the necessary rewrites for single Entries.
	 *
	 * @internal
	 * @return void
	 */
	public static function add_rewrite_endpoint() {
		global $wp_rewrite;

		$endpoint = self::get_endpoint_name();

		/** Let's make sure the endpoint array is not polluted. */
		if ( in_array( array( EP_ALL, $endpoint, $endpoint ), $wp_rewrite->endpoints ) )
			return;

		add_rewrite_endpoint( $endpoint, EP_ALL );
	}

	/**
	 * Return the endpoint name for a single Entry.
	 *
	 * Also used as the query_var for the time being.
	 *
	 * @internal
	 * @return string The name. Default: "entry"
	 */
	public static function get_endpoint_name() {
		/**
		 * @filter `gravityview_directory_endpoint` Change the slug used for single entries
		 * @param[in,out] string $endpoint Slug to use when accessing single entry. Default: `entry`
		 */
		$endpoint = apply_filters( 'gravityview_directory_endpoint', 'entry' );

		return sanitize_title( $endpoint );
	}
}
