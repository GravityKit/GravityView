<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The base \GV\Entry class.
 *
 * Contains all entry data and some processing and logic rules.
 */
abstract class Entry {

	/**
	 * @var string The identifier of the backend used for this entry.
	 * @api
	 * @since future
	 */
	public static $backend = null;

	/**
	 * @var int The ID for this entry.
	 *
	 * @api
	 * @since future
	 */
	public $ID = null;

	/**
	 * @var mixed The backing entry.
	 */
	protected $entry;
	
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
		if ( in_array( array( EP_ALL, $endpoint, $endpoint ), $wp_rewrite->endpoints ) ) {
			return;
		}

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

	/**
	 * Construct a \GV\Entry instance by ID.
	 *
	 * @param int|string $entry_id The internal entry ID.
	 *
	 * @api
	 * @since future
	 * @return \GV\Entry|null An instance of this entry or null if not found.
	 */
	public static function by_id( $entry_id ) {
		return null;
	}

	/**
	 * Return the backing entry object.
	 *
	 * @return array The backing entry object.
	 */
	public function as_entry() {
		return $this->entry;
	}
}
