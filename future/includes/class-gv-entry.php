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
	 * @since 2.0
	 */
	public static $backend = null;

	/**
	 * @var int The ID for this entry.
	 *
	 * @api
	 * @since 2.0
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
	 * @since 2.0
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

	/**
	 * Return the link to this entry in the supplied context.
	 *
	 * @api
	 * @since 2.0
	 *
	 * @param \GV\View|null $view The View context.
	 * @param \GV\Request $request The Request (current if null).
	 * @param boolean $track_directory Keep the housing directory arguments intact (used for breadcrumbs, for example). Default: true.
	 *
	 * @return string The permalink to this entry.
	 */
	public function get_permalink( \GV\View $view = null, \GV\Request $request = null, $track_directory = true ) {
		if ( is_null( $request ) ) {
			$request = &gravityview()->request;
		}

		global $post;

		$args = array();

		$view_id = is_null ( $view ) ? null : $view->ID;

		$permalink = null;

		/** This is not a regular view. */
		if ( ! $request->is_view() ) {

			/** Must be an embed of some sort. */
			if ( is_object( $post ) && is_numeric( $post->ID ) ) {
				$permalink = get_permalink( $post->ID );
				$args['gvid'] = $view_id;
			}
		}
		
		/** Fallback to regular view base. */
		if ( is_null( $permalink ) ) {
			$permalink = get_permalink( $view_id );
		}

		/**
		 * @filter `gravityview_directory_link` Modify the URL to the View "directory" context
		 * @since 1.19.4
		 * @param string $link URL to the View's "directory" context (Multiple Entries screen)
		 * @param int $post_id ID of the post to link to. If the View is embedded, it is the post or page ID
		 */
		$permalink = apply_filters( 'gravityview_directory_link', $permalink, $request->is_view() ? $view_id : ( $post ? $post->ID : null ) );

		$entry_endpoint_name = \GV\Entry::get_endpoint_name();
		$entry_slug = \GravityView_API::get_entry_slug( $this->ID, $this->as_entry() );

		/** Assemble the permalink. */
		if ( get_option( 'permalink_structure' ) && ! is_preview() ) {
			/**
			 * Make sure the $directory_link doesn't contain any query otherwise it will break when adding the entry slug.
			 * @since 1.16.5
			 */
			$link_parts = explode( '?', $permalink );

			$query = ! empty( $link_parts[1] ) ? '?' . $link_parts[1] : '';

			$permalink = trailingslashit( $link_parts[0] ) . $entry_endpoint_name . '/'. $entry_slug .'/' . $query;
		} else {
			$args[ $entry_endpoint_name ] = $entry_slug;
		}

		if ( $track_directory ) {
			if ( ! empty( $_GET['pagenum'] ) ) {
				$args['pagenum'] = intval( $_GET['pagenum'] );
			}

			if ( $sort = Utils::_GET( 'sort' ) ) {
				$args['sort'] = $sort;
				$args['dir'] = Utils::_GET( 'dir' );
			}
		}

		$permalink = add_query_arg( $args, $permalink );

		/**
		 * @filter `gravityview/entry/permalink` The permalink of this entry.
		 * @since 2.0
		 * @param string $permalink The permalink.
		 * @param \GV\Entry $entry The entry we're retrieving it for.
		 * @param \GV\View|null $view The view context.
		 * @param \GV\Request $reqeust The request context.
		 */
		return apply_filters( 'gravityview/entry/permalink', $permalink, $this, $view, $request );
	}
}
