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
		if ( in_array( array( EP_PERMALINK | EP_PERMALINK | EP_ROOT, $endpoint, $endpoint ), $wp_rewrite->endpoints ) ) {
			return;
		}

		add_rewrite_endpoint( $endpoint, EP_PAGES | EP_PERMALINK | EP_ROOT );
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
		 * Change the slug used for single entries.
		 *
		 * @since 1.0
		 *
		 * @param string $endpoint Slug to use when accessing single entry. Default: `entry`.
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
	 * @param \GV\Request   $request The Request (current if null).
	 * @param boolean       $track_directory Keep the housing directory arguments intact (used for breadcrumbs, for example). Default: true.
	 *
	 * @return string The permalink to this entry.
	 */
	public function get_permalink( \GV\View $view = null, \GV\Request $request = null, $track_directory = true ) {
		if ( is_null( $request ) ) {
			$request = &gravityview()->request;
		}

		global $post;

		$args = array();

		/**
		 * Modify whether to include passed $_GET parameters to the end of the url.
		 *
		 * @since 2.10
		 * @param bool $add_query_params Whether to include passed $_GET parameters to the end of the Entry Link URL. Default: true.
		 */
		$add_query_args = apply_filters( 'gravityview/entry_link/add_query_args', true );

		if ( $add_query_args ) {
			$args = gv_get_query_args();
		}

		$view_id = is_null( $view ) ? null : $view->ID;

		$permalink = null;

		/** This is not a regular view. */
		if ( ! $request->is_view( false ) ) {

			/** Must be an embed of some sort. */
			if ( $post instanceof \WP_Post && is_numeric( $post->ID ) ) {
				$permalink = get_permalink( $post->ID );

				$view_collection = View_Collection::from_post( $post );

				if ( 1 < $view_collection->count() ) {
					$args['gvid'] = $view_id;
				}
			}
		}

		/** Fallback to regular view base. */
		if ( is_null( $permalink ) ) {
			$permalink = get_permalink( $view_id );
		}

		/**
		 * Modify the URL to the View "directory" context.
		 *
		 * @since 1.19.4
		 *
		 * @param string $permalink URL to the View's "directory" context (Multiple Entries screen).
		 * @param int $post_id ID of the post to link to. If the View is embedded, it is the post or page ID.
		 */
		$permalink = apply_filters( 'gravityview_directory_link', $permalink, $request->is_view( false ) ? $view_id : ( $post ? $post->ID : null ) );

		$entry_endpoint_name = self::get_endpoint_name();

		$entry_slug = $this->get_slug( true, $view, $request, $track_directory );

		/** Assemble the permalink. */
		if ( get_option( 'permalink_structure' ) && ! is_preview() ) {
			/**
			 * Make sure the $directory_link doesn't contain any query otherwise it will break when adding the entry slug.
			 *
			 * @since 1.16.5
			 */
			$link_parts = explode( '?', $permalink );

			$query = ! empty( $link_parts[1] ) ? '?' . $link_parts[1] : '';

			$permalink = trailingslashit( $link_parts[0] ) . $entry_endpoint_name . '/' . $entry_slug . '/' . $query;
		} else {
			$args[ $entry_endpoint_name ] = $entry_slug;
		}

		if ( $track_directory ) {
			if ( ! empty( $_GET['pagenum'] ) ) {
				$args['pagenum'] = intval( $_GET['pagenum'] );
			}

			if ( $sort = Utils::_GET( 'sort' ) ) {
				$args['sort'] = $sort;
				$args['dir']  = Utils::_GET( 'dir' );
			}
		}

		$permalink = add_query_arg( $args, $permalink );

		/**
		 * The permalink of this entry.
		 *
		 * @since 2.0
		 *
		 * @param string         $permalink The permalink.
		 * @param \GV\Entry      $entry     The entry we're retrieving it for.
		 * @param \GV\View|null  $view      The view context.
		 * @param \GV\Request    $request   The request context.
		 */
		return apply_filters( 'gravityview/entry/permalink', $permalink, $this, $view, $request );
	}

	/**
	 * Get the entry slug
	 *
	 * @internal (for now!)
	 * @todo Should $apply_filter be default true or false? Unit tests pass either way...
	 *
	 * @since 2.7
	 *
	 * @uses \GravityView_API::get_entry_slug
	 *
	 * @param bool          $apply_filter Whether to apply the `gravityview/entry/slug` filter. Default: false.
	 * @param \GV\View|null $view The View context.
	 * @param \GV\Request   $request The Request (current if null).
	 * @param boolean       $track_directory Keep the housing directory arguments intact (used for breadcrumbs, for example). Default: true.
	 *
	 * @return string Unique slug ID, passed through `sanitize_title()`, with `gravityview/entry/slug` filter applied
	 */
	public function get_slug( $apply_filter = false, \GV\View $view = null, \GV\Request $request = null, $track_directory = true ) {

		$entry_slug = \GravityView_API::get_entry_slug( $this->ID, $this->as_entry() );

		if ( ! $apply_filter ) {
			return $entry_slug;
		}

		/**
		 * Modify the entry URL slug as needed.
		 *
		 * @since 2.2.1
		 * @param string $entry_slug The slug, sanitized with sanitize_title()
		 * @param null|\GV\Entry $this The entry object.
		 * @param null|\GV\View $view The view object.
		 * @param null|\GV\Request $request The request.
		 * @param bool $track_directory Whether the directory is tracked.
		 */
		$entry_slug = apply_filters( 'gravityview/entry/slug', $entry_slug, $this, $view, $request, $track_directory );

		return $entry_slug;
	}

	/**
	 * Is this a multi-entry (joined entry).
	 *
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function is_multi() {
		return $this instanceof Multi_Entry;
	}

	/**
	 * If this is a Multi_Entry filter it by Field
	 *
	 * @since 2.2
	 *
	 * @param \GV\Field $field The field to filter by.
	 * @param int       $fallback A fallback form_id if the field supplied is invalid.
	 *
	 * @return \GV\Entry|null A \GV\Entry or null if not found.
	 */
	public function from_field( $field, $fallback = null ) {
		if ( ! $this->is_multi() ) {
			return $this;
		}
		return Utils::get( $this, $field->form_id, $fallback );
	}
}
