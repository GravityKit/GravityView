<?php

use WP_CLI\CommandWithDBObject;
use WP_CLI\Fetchers\Site as SiteFetcher;
use WP_CLI\Iterators\Query as QueryIterator;
use WP_CLI\Iterators\Table as TableIterator;
use WP_CLI\Utils;
use WP_CLI\Formatter;

/**
 * Creates, deletes, empties, moderates, and lists one or more sites on a multisite installation.
 *
 * ## EXAMPLES
 *
 *     # Create site
 *     $ wp site create --slug=example
 *     Success: Site 3 created: www.example.com/example/
 *
 *     # Output a simple list of site URLs
 *     $ wp site list --field=url
 *     http://www.example.com/
 *     http://www.example.com/subdir/
 *
 *     # Delete site
 *     $ wp site delete 123
 *     Are you sure you want to delete the 'http://www.example.com/example' site? [y/n] y
 *     Success: The site at 'http://www.example.com/example' was deleted.
 *
 * @package wp-cli
 */
class Site_Command extends CommandWithDBObject {

	protected $obj_type   = 'site';
	protected $obj_id_key = 'blog_id';

	public function __construct() {
		$this->fetcher = new SiteFetcher();
	}

	/**
	 * Delete comments.
	 */
	private function empty_comments() {
		global $wpdb;

		// Empty comments and comment cache
		$comment_ids = $wpdb->get_col( "SELECT comment_ID FROM $wpdb->comments" );
		foreach ( $comment_ids as $comment_id ) {
			wp_cache_delete( $comment_id, 'comment' );
			wp_cache_delete( $comment_id, 'comment_meta' );
		}
		$wpdb->query( "TRUNCATE $wpdb->comments" );
		$wpdb->query( "TRUNCATE $wpdb->commentmeta" );
	}

	/**
	 * Delete all posts.
	 */
	private function empty_posts() {
		global $wpdb;

		// Empty posts and post cache
		$posts_query = "SELECT ID FROM $wpdb->posts";
		$posts       = new QueryIterator( $posts_query, 10000 );

		$taxonomies = get_taxonomies();

		while ( $posts->valid() ) {
			$post_id = $posts->current()->ID;

			wp_cache_delete( $post_id, 'posts' );
			wp_cache_delete( $post_id, 'post_meta' );
			foreach ( $taxonomies as $taxonomy ) {
				wp_cache_delete( $post_id, "{$taxonomy}_relationships" );
			}
			wp_cache_delete( $wpdb->blogid . '-' . $post_id, 'global-posts' );

			$posts->next();
		}
		$wpdb->query( "TRUNCATE $wpdb->posts" );
		$wpdb->query( "TRUNCATE $wpdb->postmeta" );
	}

	/**
	 * Delete terms, taxonomies, and tax relationships.
	 */
	private function empty_taxonomies() {
		global $wpdb;

		// Empty taxonomies and terms
		$terms      = $wpdb->get_results( "SELECT term_id, taxonomy FROM $wpdb->term_taxonomy" );
		$taxonomies = [];
		foreach ( (array) $terms as $term ) {
			$taxonomies[] = $term->taxonomy;
			wp_cache_delete( $term->term_id, $term->taxonomy );
		}

		$taxonomies = array_unique( $taxonomies );
		$cleaned    = [];
		foreach ( $taxonomies as $taxonomy ) {
			if ( isset( $cleaned[ $taxonomy ] ) ) {
				continue;
			}
			$cleaned[ $taxonomy ] = true;

			wp_cache_delete( 'all_ids', $taxonomy );
			wp_cache_delete( 'get', $taxonomy );
			delete_option( "{$taxonomy}_children" );
		}
		$wpdb->query( "TRUNCATE $wpdb->terms" );
		$wpdb->query( "TRUNCATE $wpdb->term_taxonomy" );
		$wpdb->query( "TRUNCATE $wpdb->term_relationships" );
		if ( ! empty( $wpdb->termmeta ) ) {
			$wpdb->query( "TRUNCATE $wpdb->termmeta" );
		}
	}

	/**
	 * Delete all links, link_category terms, and related cache.
	 */
	private function empty_links() {
		global $wpdb;

		// Remove links and related cached data.
		$links_query = "SELECT link_id FROM {$wpdb->links}";
		$links       = new QueryIterator( $links_query, 10000 );

		// Remove bookmarks cache group.
		wp_cache_delete( 'get_bookmarks', 'bookmark' );

		while ( $links->valid() ) {
			$link_id = $links->current()->link_id;

			// Remove cache for the link.
			wp_delete_object_term_relationships( $link_id, 'link_category' );
			wp_cache_delete( $link_id, 'bookmark' );
			clean_object_term_cache( $link_id, 'link' );

			$links->next();
		}

		// Empty the table once link related cache and term is removed.
		$wpdb->query( "TRUNCATE {$wpdb->links}" );
	}

	/**
	 * Insert default terms.
	 */
	private function insert_default_terms() {
		global $wpdb;

		// Default category
		$cat_name = __( 'Uncategorized' );

		/* translators: Default category slug */
		$cat_slug = sanitize_title( _x( 'Uncategorized', 'Default category slug' ) );

		if ( global_terms_enabled() ) {
			$cat_id = $wpdb->get_var( $wpdb->prepare( "SELECT cat_ID FROM {$wpdb->sitecategories} WHERE category_nicename = %s", $cat_slug ) );
			if ( null === $cat_id ) {
				$wpdb->insert(
					$wpdb->sitecategories,
					[
						'cat_ID'            => 0,
						'cat_name'          => $cat_name,
						'category_nicename' => $cat_slug,
						'last_updated'      => current_time(
							'mysql',
							true
						),
					]
				);
				$cat_id = $wpdb->insert_id;
			}
			update_option( 'default_category', $cat_id );
		} else {
			$cat_id = 1;
		}

		$wpdb->insert(
			$wpdb->terms,
			[
				'term_id'    => $cat_id,
				'name'       => $cat_name,
				'slug'       => $cat_slug,
				'term_group' => 0,
			]
		);
		$wpdb->insert(
			$wpdb->term_taxonomy,
			[
				'term_id'     => $cat_id,
				'taxonomy'    => 'category',
				'description' => '',
				'parent'      => 0,
				'count'       => 0,
			]
		);
	}

	/**
	 * Reset option values to default.
	 */
	private function reset_options() {
		// Reset Privacy Policy value to prevent error.
		update_option( 'wp_page_for_privacy_policy', 0 );

		// Reset sticky posts option.
		update_option( 'sticky_posts', [] );
	}

	/**
	 * Empties a site of its content (posts, comments, terms, and meta).
	 *
	 * Truncates posts, comments, and terms tables to empty a site of its
	 * content. Doesn't affect site configuration (options) or users.
	 *
	 * If running a persistent object cache, make sure to flush the cache
	 * after emptying the site, as the cache values will be invalid otherwise.
	 *
	 * To also empty custom database tables, you'll need to hook into command
	 * execution:
	 *
	 * ```
	 * WP_CLI::add_hook( 'after_invoke:site empty', function(){
	 *     global $wpdb;
	 *     foreach( array( 'p2p', 'p2pmeta' ) as $table ) {
	 *         $table = $wpdb->$table;
	 *         $wpdb->query( "TRUNCATE $table" );
	 *     }
	 * });
	 * ```
	 *
	 * ## OPTIONS
	 *
	 * [--uploads]
	 * : Also delete *all* files in the site's uploads directory.
	 *
	 * [--yes]
	 * : Proceed to empty the site without a confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site empty
	 *     Are you sure you want to empty the site at http://www.example.com of all posts, links, comments, and terms? [y/n] y
	 *     Success: The site at 'http://www.example.com' was emptied.
	 *
	 * @subcommand empty
	 */
	public function empty_( $args, $assoc_args ) {

		$upload_message = '';
		if ( Utils\get_flag_value( $assoc_args, 'uploads' ) ) {
			$upload_message = ', and delete its uploads directory';
		}

		WP_CLI::confirm( "Are you sure you want to empty the site at '" . site_url() . "' of all posts, links, comments, and terms" . $upload_message . '?', $assoc_args );

		$this->empty_posts();
		$this->empty_links();
		$this->empty_comments();
		$this->empty_taxonomies();
		$this->insert_default_terms();
		$this->reset_options();

		if ( ! empty( $upload_message ) ) {
			$upload_dir = wp_upload_dir();
			$files      = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $upload_dir['basedir'], RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::CHILD_FIRST
			);

			$files_to_unlink       = [];
			$directories_to_delete = [];
			$is_main_site          = is_main_site();
			foreach ( $files as $fileinfo ) {
				$realpath = $fileinfo->getRealPath();
				// Don't clobber subsites when operating on the main site
				if ( $is_main_site && false !== stripos( $realpath, '/sites/' ) ) {
					continue;
				}
				if ( $fileinfo->isDir() ) {
					$directories_to_delete[] = $realpath;
				} else {
					$files_to_unlink[] = $realpath;
				}
			}
			foreach ( $files_to_unlink as $file ) {
				unlink( $file );
			}
			foreach ( $directories_to_delete as $directory ) {
				// Directory could be main sites directory '/sites' which may be non-empty.
				@rmdir( $directory );
			}
			// May be non-empty if '/sites' still around.
			@rmdir( $upload_dir['basedir'] );
		}

		WP_CLI::success( "The site at '" . site_url() . "' was emptied." );
	}

	/**
	 * Deletes a site in a multisite installation.
	 *
	 * ## OPTIONS
	 *
	 * [<site-id>]
	 * : The id of the site to delete. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the blog to be deleted. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * [--keep-tables]
	 * : Delete the blog from the list, but don't drop it's tables.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site delete 123
	 *     Are you sure you want to delete the http://www.example.com/example site? [y/n] y
	 *     Success: The site at 'http://www.example.com/example' was deleted.
	 */
	public function delete( $args, $assoc_args ) {
		if ( ! is_multisite() ) {
			WP_CLI::error( 'This is not a multisite installation.' );
		}

		if ( isset( $assoc_args['slug'] ) ) {
			$blog = get_blog_details( trim( $assoc_args['slug'], '/' ) );
		} else {
			if ( empty( $args ) ) {
				WP_CLI::error( 'Need to specify a blog id.' );
			}

			$blog_id = $args[0];

			if ( is_main_site( $blog_id ) ) {
				WP_CLI::error( 'You cannot delete the root site.' );
			}

			$blog = get_blog_details( $blog_id );
		}

		if ( ! $blog ) {
			WP_CLI::error( 'Site not found.' );
		}

		$site_url = trailingslashit( $blog->siteurl );

		WP_CLI::confirm( "Are you sure you want to delete the '{$site_url}' site?", $assoc_args );

		wpmu_delete_blog( $blog->blog_id, ! Utils\get_flag_value( $assoc_args, 'keep-tables' ) );

		WP_CLI::success( "The site at '{$site_url}' was deleted." );
	}

	/**
	 * Creates a site in a multisite installation.
	 *
	 * ## OPTIONS
	 *
	 * --slug=<slug>
	 * : Path for the new site. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * [--title=<title>]
	 * : Title of the new site. Default: prettified slug.
	 *
	 * [--email=<email>]
	 * : Email for Admin user. User will be created if none exists. Assignement to Super Admin if not included.
	 *
	 * [--network_id=<network-id>]
	 * : Network to associate new site with. Defaults to current network (typically 1).
	 *
	 * [--private]
	 * : If set, the new site will be non-public (not indexed)
	 *
	 * [--porcelain]
	 * : If set, only the site id will be output on success.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site create --slug=example
	 *     Success: Site 3 created: http://www.example.com/example/
	 */
	public function create( $args, $assoc_args ) {
		if ( ! is_multisite() ) {
			WP_CLI::error( 'This is not a multisite installation.' );
		}

		global $wpdb, $current_site;

		$base  = $assoc_args['slug'];
		$title = Utils\get_flag_value( $assoc_args, 'title', ucfirst( $base ) );

		$email = empty( $assoc_args['email'] ) ? '' : $assoc_args['email'];

		// Network
		if ( ! empty( $assoc_args['network_id'] ) ) {
			$network = $this->get_network( $assoc_args['network_id'] );
			if ( false === $network ) {
				WP_CLI::error( "Network with id {$assoc_args['network_id']} does not exist." );
			}
		} else {
			$network = $current_site;
		}

		$public = ! Utils\get_flag_value( $assoc_args, 'private' );

		// Sanitize
		if ( preg_match( '|^([a-zA-Z0-9-])+$|', $base ) ) {
			$base = strtolower( $base );
		}

		// If not a subdomain install, make sure the domain isn't a reserved word
		if ( ! is_subdomain_install() ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Calling WordPress native hook.
			$subdirectory_reserved_names = apply_filters( 'subdirectory_reserved_names', [ 'page', 'comments', 'blog', 'files', 'feed' ] );
			if ( in_array( $base, $subdirectory_reserved_names, true ) ) {
				WP_CLI::error( 'The following words are reserved and cannot be used as blog names: ' . implode( ', ', $subdirectory_reserved_names ) );
			}
		}

		// Check for valid email, if not, use the first Super Admin found
		// Probably a more efficient way to do this so we dont query for the
		// User twice if super admin
		$email = sanitize_email( $email );
		if ( empty( $email ) || ! is_email( $email ) ) {
			$super_admins = get_super_admins();
			$email        = '';
			if ( ! empty( $super_admins ) && is_array( $super_admins ) ) {
				// Just get the first one
				$super_login = $super_admins[0];
				$super_user  = get_user_by( 'login', $super_login );
				if ( $super_user ) {
					$email = $super_user->user_email;
				}
			}
		}

		if ( is_subdomain_install() ) {
			$newdomain = $base . '.' . preg_replace( '|^www\.|', '', $current_site->domain );
			$path      = $current_site->path;
		} else {
			$newdomain = $current_site->domain;
			$path      = $current_site->path . $base . '/';
		}

		$user_id = email_exists( $email );
		if ( ! $user_id ) { // Create a new user with a random password
			$password = wp_generate_password( 12, false );
			$user_id  = wpmu_create_user( $base, $password, $email );
			if ( false === $user_id ) {
				WP_CLI::error( "Can't create user." );
			} else {
				User_Command::wp_new_user_notification( $user_id, $password );
			}
		}

		$wpdb->hide_errors();
		$title = wp_slash( $title );
		$id    = wpmu_create_blog( $newdomain, $path, $title, $user_id, [ 'public' => $public ], $network->id );
		$wpdb->show_errors();
		if ( ! is_wp_error( $id ) ) {
			if ( ! is_super_admin( $user_id ) && ! get_user_option( 'primary_blog', $user_id ) ) {
				update_user_option( $user_id, 'primary_blog', $id, true );
			}
		} else {
			WP_CLI::error( $id->get_error_message() );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( $id );
		} else {
			$site_url = trailingslashit( get_site_url( $id ) );
			WP_CLI::success( "Site {$id} created: {$site_url}" );
		}
	}

	/**
	 * Gets network data for a given id.
	 *
	 * @param int     $network_id
	 * @return bool|array False if no network found with given id, array otherwise
	 */
	private function get_network( $network_id ) {
		global $wpdb;

		// Load network data
		$networks = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->site WHERE id = %d",
				$network_id
			)
		);

		if ( ! empty( $networks ) ) {
			// Only care about domain and path which are set here
			return $networks[0];
		}

		return false;
	}

	/**
	 * Lists all sites in a multisite installation.
	 *
	 * ## OPTIONS
	 *
	 * [--network=<id>]
	 * : The network to which the sites belong.
	 *
	 * [--<field>=<value>]
	 * : Filter by one or more fields (see "Available Fields" section). However,
	 * 'url' isn't an available filter, because it's created from domain + path.
	 *
	 * [--site__in=<value>]
	 * : Only list the sites with these blog_id values (comma-separated).
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each site.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of fields to show.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - count
	 *   - ids
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each site:
	 *
	 * * blog_id
	 * * url
	 * * last_updated
	 * * registered
	 *
	 * These fields are optionally available:
	 *
	 * * site_id
	 * * domain
	 * * path
	 * * public
	 * * archived
	 * * mature
	 * * spam
	 * * deleted
	 * * lang_id
	 *
	 * ## EXAMPLES
	 *
	 *     # Output a simple list of site URLs
	 *     $ wp site list --field=url
	 *     http://www.example.com/
	 *     http://www.example.com/subdir/
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		if ( ! is_multisite() ) {
			WP_CLI::error( 'This is not a multisite installation.' );
		}

		global $wpdb;

		if ( isset( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = preg_split( '/,[ \t]*/', $assoc_args['fields'] );
		}

		$defaults   = [
			'format' => 'table',
			'fields' => [ 'blog_id', 'url', 'last_updated', 'registered' ],
		];
		$assoc_args = array_merge( $defaults, $assoc_args );

		$where  = [];
		$append = '';

		$site_cols = [ 'blog_id', 'last_updated', 'registered', 'site_id', 'domain', 'path', 'public', 'archived', 'mature', 'spam', 'deleted', 'lang_id' ];
		foreach ( $site_cols as $col ) {
			if ( isset( $assoc_args[ $col ] ) ) {
				$where[ $col ] = $assoc_args[ $col ];
			}
		}

		if ( isset( $assoc_args['site__in'] ) ) {
			$where['blog_id'] = explode( ',', $assoc_args['site__in'] );
			$append           = 'ORDER BY FIELD( blog_id, ' . implode( ',', array_map( 'intval', $where['blog_id'] ) ) . ' )';
		}

		if ( isset( $assoc_args['network'] ) ) {
			$where['site_id'] = $assoc_args['network'];
		}

		$iterator_args = [
			'table'  => $wpdb->blogs,
			'where'  => $where,
			'append' => $append,
		];

		$iterator = new TableIterator( $iterator_args );

		$iterator = Utils\iterator_map(
			$iterator,
			function( $blog ) {
				$blog->url = trailingslashit( get_site_url( $blog->blog_id ) );
				return $blog;
			}
		);

		if ( ! empty( $assoc_args['format'] ) && 'ids' === $assoc_args['format'] ) {
			$sites     = iterator_to_array( $iterator );
			$ids       = wp_list_pluck( $sites, 'blog_id' );
			$formatter = new Formatter( $assoc_args, null, 'site' );
			$formatter->display_items( $ids );
		} else {
			$formatter = new Formatter( $assoc_args, null, 'site' );
			$formatter->display_items( $iterator );
		}
	}

	/**
	 * Archives one or more sites.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more IDs of sites to archive.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site archive 123
	 *     Success: Site 123 archived.
	 */
	public function archive( $args ) {
		$this->update_site_status( $args, 'archived', 1 );
	}

	/**
	 * Unarchives one or more sites.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more IDs of sites to unarchive.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site unarchive 123
	 *     Success: Site 123 unarchived.
	 */
	public function unarchive( $args ) {
		$this->update_site_status( $args, 'archived', 0 );
	}

	/**
	 * Activates one or more sites.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more IDs of sites to activate.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site activate 123
	 *     Success: Site 123 activated.
	 */
	public function activate( $args ) {
		$this->update_site_status( $args, 'deleted', 0 );
	}

	/**
	 * Deactivates one or more sites.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more IDs of sites to deactivate.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site deactivate 123
	 *     Success: Site 123 deactivated.
	 */
	public function deactivate( $args ) {
		$this->update_site_status( $args, 'deleted', 1 );
	}

	/**
	 * Marks one or more sites as spam.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more IDs of sites to be marked as spam.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site spam 123
	 *     Success: Site 123 marked as spam.
	 */
	public function spam( $args ) {
		$this->update_site_status( $args, 'spam', 1 );
	}

	/**
	 * Removes one or more sites from spam.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more IDs of sites to remove from spam.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site unspam 123
	 *     Success: Site 123 removed from spam.
	 *
	 * @subcommand unspam
	 */
	public function unspam( $args ) {
		$this->update_site_status( $args, 'spam', 0 );
	}

	/**
	 * Sets one or more sites as mature.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more IDs of sites to set as mature.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site mature 123
	 *     Success: Site 123 marked as mature.
	 */
	public function mature( $args ) {
		$this->update_site_status( $args, 'mature', 1 );
	}

	/**
	 * Sets one or more sites as immature.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more IDs of sites to set as unmature.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site general 123
	 *     Success: Site 123 marked as unmature.
	 */
	public function unmature( $args ) {
		$this->update_site_status( $args, 'mature', 0 );
	}

	/**
	 * Sets one or more sites as public.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more IDs of sites to set as public.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site public 123
	 *     Success: Site 123 marked as public.
	 *
	 * @subcommand public
	 */
	public function set_public( $args ) {
		$this->update_site_status( $args, 'public', 1 );
	}

	/**
	 * Sets one or more sites as private.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more IDs of sites to set as private.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site private 123
	 *     Success: Site 123 marked as private.
	 *
	 * @subcommand private
	 */
	public function set_private( $args ) {
		$this->update_site_status( $args, 'public', 0 );
	}

	private function update_site_status( $ids, $pref, $value ) {
		$value = (int) $value;

		switch ( $pref ) {
			case 'archived':
				$action = $value ? 'archived' : 'unarchived';
				break;
			case 'deleted':
				$action = $value ? 'deactivated' : 'activated';
				break;
			case 'mature':
				$action = $value ? 'marked as mature' : 'marked as unmature';
				break;
			case 'public':
				$action = $value ? 'marked as public' : 'marked as private';
				break;
			case 'spam':
				$action = $value ? 'marked as spam' : 'removed from spam';
				break;
		}

		foreach ( $ids as $site_id ) {
			$site = $this->fetcher->get_check( $site_id );

			if ( is_main_site( $site->blog_id ) ) {
				WP_CLI::warning( 'You are not allowed to change the main site.' );
				continue;
			}

			$old_value = (int) get_blog_status( $site->blog_id, $pref );

			if ( $value === $old_value ) {
				WP_CLI::warning( "Site {$site->blog_id} already {$action}." );
				continue;
			}

			update_blog_status( $site->blog_id, $pref, $value );
			WP_CLI::success( "Site {$site->blog_id} {$action}." );
		}
	}
}
