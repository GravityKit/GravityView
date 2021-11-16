<?php

use WP_CLI\CommandWithDBObject;
use WP_CLI\Entity\Utils as EntityUtils;
use WP_CLI\Fetchers\Post as PostFetcher;
use WP_CLI\Fetchers\User as UserFetcher;
use WP_CLI\Utils;

/**
 * Manages posts, content, and meta.
 *
 * ## EXAMPLES
 *
 *     # Create a new post.
 *     $ wp post create --post_type=post --post_title='A sample post'
 *     Success: Created post 123.
 *
 *     # Update an existing post.
 *     $ wp post update 123 --post_status=draft
 *     Success: Updated post 123.
 *
 *     # Delete an existing post.
 *     $ wp post delete 123
 *     Success: Trashed post 123.
 *
 * @package wp-cli
 */
class Post_Command extends CommandWithDBObject {

	protected $obj_type   = 'post';
	protected $obj_fields = [
		'ID',
		'post_title',
		'post_name',
		'post_date',
		'post_status',
	];

	public function __construct() {
		$this->fetcher = new PostFetcher();
	}

	/**
	 * Creates a new post.
	 *
	 * ## OPTIONS
	 *
	 * [--post_author=<post_author>]
	 * : The ID of the user who added the post. Default is the current user ID.
	 *
	 * [--post_date=<post_date>]
	 * : The date of the post. Default is the current time.
	 *
	 * [--post_date_gmt=<post_date_gmt>]
	 * : The date of the post in the GMT timezone. Default is the value of $post_date.
	 *
	 * [--post_content=<post_content>]
	 * : The post content. Default empty.
	 *
	 * [--post_content_filtered=<post_content_filtered>]
	 * : The filtered post content. Default empty.
	 *
	 * [--post_title=<post_title>]
	 * : The post title. Default empty.
	 *
	 * [--post_excerpt=<post_excerpt>]
	 * : The post excerpt. Default empty.
	 *
	 * [--post_status=<post_status>]
	 * : The post status. Default 'draft'.
	 *
	 * [--post_type=<post_type>]
	 * : The post type. Default 'post'.
	 *
	 * [--comment_status=<comment_status>]
	 * : Whether the post can accept comments. Accepts 'open' or 'closed'. Default is the value of 'default_comment_status' option.
	 *
	 * [--ping_status=<ping_status>]
	 * : Whether the post can accept pings. Accepts 'open' or 'closed'. Default is the value of 'default_ping_status' option.
	 *
	 * [--post_password=<post_password>]
	 * : The password to access the post. Default empty.
	 *
	 * [--post_name=<post_name>]
	 * : The post name. Default is the sanitized post title when creating a new post.
	 *
	 * [--from-post=<post_id>]
	 * : Post id of a post to be duplicated.
	 *
	 * [--to_ping=<to_ping>]
	 * : Space or carriage return-separated list of URLs to ping. Default empty.
	 *
	 * [--pinged=<pinged>]
	 * : Space or carriage return-separated list of URLs that have been pinged. Default empty.
	 *
	 * [--post_modified=<post_modified>]
	 * : The date when the post was last modified. Default is the current time.
	 *
	 * [--post_modified_gmt=<post_modified_gmt>]
	 * : The date when the post was last modified in the GMT timezone. Default is the current time.
	 *
	 * [--post_parent=<post_parent>]
	 * : Set this for the post it belongs to, if any. Default 0.
	 *
	 * [--menu_order=<menu_order>]
	 * : The order the post should be displayed in. Default 0.
	 *
	 * [--post_mime_type=<post_mime_type>]
	 * : The mime type of the post. Default empty.
	 *
	 * [--guid=<guid>]
	 * : Global Unique ID for referencing the post. Default empty.
	 *
	 * [--post_category=<post_category>]
	 * : Array of category names, slugs, or IDs. Defaults to value of the 'default_category' option.
	 *
	 * [--tags_input=<tags_input>]
	 * : Array of tag names, slugs, or IDs. Default empty.
	 *
	 * [--tax_input=<tax_input>]
	 * : Array of taxonomy terms keyed by their taxonomy name. Default empty.
	 *
	 * [--meta_input=<meta_input>]
	 * : Array in JSON format of post meta values keyed by their post meta key. Default empty.
	 *
	 * [<file>]
	 * : Read post content from <file>. If this value is present, the
	 *     `--post_content` argument will be ignored.
	 *
	 *   Passing `-` as the filename will cause post content to
	 *   be read from STDIN.
	 *
	 * [--<field>=<value>]
	 * : Associative args for the new post. See wp_insert_post().
	 *
	 * [--edit]
	 * : Immediately open system's editor to write or edit post content.
	 *
	 *   If content is read from a file, from STDIN, or from the `--post_content`
	 *   argument, that text will be loaded into the editor.
	 *
	 * [--porcelain]
	 * : Output just the new post id.
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     # Create post and schedule for future
	 *     $ wp post create --post_type=page --post_title='A future post' --post_status=future --post_date='2020-12-01 07:00:00'
	 *     Success: Created post 1921.
	 *
	 *     # Create post with content from given file
	 *     $ wp post create ./post-content.txt --post_category=201,345 --post_title='Post from file'
	 *     Success: Created post 1922.
	 *
	 *     # Create a post with multiple meta values.
	 *     $ wp post create --post_title='A post' --post_content='Just a small post.' --meta_input='{"key1":"value1","key2":"value2"}'
	 *     Success: Created post 1923.
	 *
	 *     # Create a duplicate post from existing posts.
	 *     $ wp post create --from-post=123 --post_title='Different Title'
	 *     Success: Created post 2350.
	 */
	public function create( $args, $assoc_args ) {
		if ( ! empty( $args[0] ) ) {
			$assoc_args['post_content'] = $this->read_from_file_or_stdin( $args[0] );
		}

		if ( Utils\get_flag_value( $assoc_args, 'edit' ) ) {
			$input = Utils\get_flag_value( $assoc_args, 'post_content', '' );

			$output = $this->_edit( $input, 'WP-CLI: New Post' );
			if ( $output ) {
				$assoc_args['post_content'] = $output;
			} else {
				$assoc_args['post_content'] = $input;
			}
		}

		if ( isset( $assoc_args['post_category'] ) ) {
			$assoc_args['post_category'] = $this->get_category_ids( $assoc_args['post_category'] );
		}

		if ( isset( $assoc_args['meta_input'] ) && Utils\wp_version_compare( '4.4', '<' ) ) {
			WP_CLI::warning( "The 'meta_input' field was only introduced in WordPress 4.4 so will have no effect." );
		}

		$array_arguments = [ 'meta_input' ];
		$assoc_args      = Utils\parse_shell_arrays( $assoc_args, $array_arguments );

		if ( isset( $assoc_args['from-post'] ) ) {
			$post     = $this->fetcher->get_check( $assoc_args['from-post'] );
			$post_arr = get_object_vars( $post );
			$post_id  = $post_arr['ID'];
			unset( $post_arr['post_date'] );
			unset( $post_arr['post_date_gmt'] );
			unset( $post_arr['guid'] );
			unset( $post_arr['ID'] );

			if ( empty( $assoc_args['meta_input'] ) ) {
				$assoc_args['meta_input'] = $this->get_metadata( $post_id );
			}
			if ( empty( $assoc_args['post_category'] ) ) {
				$post_arr['post_category'] = $this->get_category( $post_id );
			}
			if ( empty( $assoc_args['tags_input'] ) ) {
				$post_arr['tags_input'] = $this->get_tags( $post_id );
			}
			$assoc_args = array_merge( $post_arr, $assoc_args );
		}

		$assoc_args = wp_slash( $assoc_args );
		parent::_create(
			$args,
			$assoc_args,
			function ( $params ) {
				return wp_insert_post( $params, true );
			}
		);
	}

	/**
	 * Updates one or more existing posts.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more IDs of posts to update.
	 *
	 * [--post_author=<post_author>]
	 * : The ID of the user who added the post. Default is the current user ID.
	 *
	 * [--post_date=<post_date>]
	 * : The date of the post. Default is the current time.
	 *
	 * [--post_date_gmt=<post_date_gmt>]
	 * : The date of the post in the GMT timezone. Default is the value of $post_date.
	 *
	 * [--post_content=<post_content>]
	 * : The post content. Default empty.
	 *
	 * [--post_content_filtered=<post_content_filtered>]
	 * : The filtered post content. Default empty.
	 *
	 * [--post_title=<post_title>]
	 * : The post title. Default empty.
	 *
	 * [--post_excerpt=<post_excerpt>]
	 * : The post excerpt. Default empty.
	 *
	 * [--post_status=<post_status>]
	 * : The post status. Default 'draft'.
	 *
	 * [--post_type=<post_type>]
	 * : The post type. Default 'post'.
	 *
	 * [--comment_status=<comment_status>]
	 * : Whether the post can accept comments. Accepts 'open' or 'closed'. Default is the value of 'default_comment_status' option.
	 *
	 * [--ping_status=<ping_status>]
	 * : Whether the post can accept pings. Accepts 'open' or 'closed'. Default is the value of 'default_ping_status' option.
	 *
	 * [--post_password=<post_password>]
	 * : The password to access the post. Default empty.
	 *
	 * [--post_name=<post_name>]
	 * : The post name. Default is the sanitized post title when creating a new post.
	 *
	 * [--to_ping=<to_ping>]
	 * : Space or carriage return-separated list of URLs to ping. Default empty.
	 *
	 * [--pinged=<pinged>]
	 * : Space or carriage return-separated list of URLs that have been pinged. Default empty.
	 *
	 * [--post_modified=<post_modified>]
	 * : The date when the post was last modified. Default is the current time.
	 *
	 * [--post_modified_gmt=<post_modified_gmt>]
	 * : The date when the post was last modified in the GMT timezone. Default is the current time.
	 *
	 * [--post_parent=<post_parent>]
	 * : Set this for the post it belongs to, if any. Default 0.
	 *
	 * [--menu_order=<menu_order>]
	 * : The order the post should be displayed in. Default 0.
	 *
	 * [--post_mime_type=<post_mime_type>]
	 * : The mime type of the post. Default empty.
	 *
	 * [--guid=<guid>]
	 * : Global Unique ID for referencing the post. Default empty.
	 *
	 * [--post_category=<post_category>]
	 * : Array of category names, slugs, or IDs. Defaults to value of the 'default_category' option.
	 *
	 * [--tags_input=<tags_input>]
	 * : Array of tag names, slugs, or IDs. Default empty.
	 *
	 * [--tax_input=<tax_input>]
	 * : Array of taxonomy terms keyed by their taxonomy name. Default empty.
	 *
	 * [--meta_input=<meta_input>]
	 * : Array in JSON format of post meta values keyed by their post meta key. Default empty.
	 *
	 * [<file>]
	 * : Read post content from <file>. If this value is present, the
	 *     `--post_content` argument will be ignored.
	 *
	 *   Passing `-` as the filename will cause post content to
	 *   be read from STDIN.
	 *
	 * --<field>=<value>
	 * : One or more fields to update. See wp_insert_post().
	 *
	 * [--defer-term-counting]
	 * : Recalculate term count in batch, for a performance boost.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp post update 123 --post_name=something --post_status=draft
	 *     Success: Updated post 123.
	 *
	 *     # Update a post with multiple meta values.
	 *     $ wp post update 123 --meta_input='{"key1":"value1","key2":"value2"}'
	 *     Success: Updated post 123.
	 */
	public function update( $args, $assoc_args ) {

		foreach ( $args as $key => $arg ) {
			if ( is_numeric( $arg ) ) {
				continue;
			}

			$assoc_args['post_content'] = $this->read_from_file_or_stdin( $arg );
			unset( $args[ $key ] );
			break;
		}

		if ( isset( $assoc_args['post_category'] ) ) {
			$assoc_args['post_category'] = $this->get_category_ids( $assoc_args['post_category'] );
		}

		if ( isset( $assoc_args['meta_input'] ) && Utils\wp_version_compare( '4.4', '<' ) ) {
			WP_CLI::warning( "The 'meta_input' field was only introduced in WordPress 4.4 so will have no effect." );
		}

		$array_arguments = [ 'meta_input' ];
		$assoc_args      = Utils\parse_shell_arrays( $assoc_args, $array_arguments );

		$assoc_args = wp_slash( $assoc_args );
		parent::_update(
			$args,
			$assoc_args,
			function ( $params ) {
				return wp_update_post( $params, true );
			}
		);
	}

	/**
	 * Launches system editor to edit post content.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post to edit.
	 *
	 * ## EXAMPLES
	 *
	 *     # Launch system editor to edit post
	 *     $ wp post edit 123
	 */
	public function edit( $args, $assoc_args ) {
		$post = $this->fetcher->get_check( $args[0] );

		$result = $this->_edit( $post->post_content, "WP-CLI post {$post->ID}" );

		if ( false === $result ) {
			WP_CLI::warning( 'No change made to post content.', 'Aborted' );
		} else {
			$this->update( $args, [ 'post_content' => $result ] );
		}
	}

	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore -- Whitelisting to provide backward compatibility to classes possibly extending this class.
	protected function _edit( $content, $title ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Calling native WordPress hook.
		$content = apply_filters( 'the_editor_content', $content );
		$output  = Utils\launch_editor_for_input( $content, $title );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Calling native WordPress hook.
		return ( is_string( $output ) ) ? apply_filters( 'content_save_pre', $output ) : $output;
	}

	/**
	 * Gets details about a post.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post to get.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole post, returns the value of a single field.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Save the post content to a file
	 *     $ wp post get 123 --field=content > file.txt
	 */
	public function get( $args, $assoc_args ) {
		$post = $this->fetcher->get_check( $args[0] );

		$post_arr = get_object_vars( $post );
		unset( $post_arr['filter'] );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $post_arr );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $post_arr );
	}

	/**
	 * Deletes an existing post.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more IDs of posts to delete.
	 *
	 * [--force]
	 * : Skip the trash bin.
	 *
	 * [--defer-term-counting]
	 * : Recalculate term count in batch, for a performance boost.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete post skipping trash
	 *     $ wp post delete 123 --force
	 *     Success: Deleted post 123.
	 *
	 *     # Delete all pages
	 *     $ wp post delete $(wp post list --post_type='page' --format=ids)
	 *     Success: Trashed post 1164.
	 *     Success: Trashed post 1186.
	 *
	 *     # Delete all posts in the trash
	 *     $ wp post delete $(wp post list --post_status=trash --format=ids)
	 *     Success: Deleted post 1268.
	 *     Success: Deleted post 1294.
	 */
	public function delete( $args, $assoc_args ) {
		$defaults   = [ 'force' => false ];
		$assoc_args = array_merge( $defaults, $assoc_args );

		parent::_delete( $args, $assoc_args, [ $this, 'delete_callback' ] );
	}

	/**
	 * Callback used to delete a post.
	 *
	 * @param $post_id
	 * @param $assoc_args
	 * @return array
	 */
	protected function delete_callback( $post_id, $assoc_args ) {
		$status    = get_post_status( $post_id );
		$post_type = get_post_type( $post_id );

		if ( ! $assoc_args['force']
			&& ( 'post' !== $post_type && 'page' !== $post_type ) ) {
			return [
				'error',
				"Posts of type '{$post_type}' do not support being sent to trash.\n"
				. 'Please use the --force flag to skip trash and delete them permanently.',
			];
		}

		if ( ! wp_delete_post( $post_id, $assoc_args['force'] ) ) {
			return [ 'error', "Failed deleting post {$post_id}." ];
		}

		$action = $assoc_args['force'] || 'trash' === $status || 'revision' === $post_type ? 'Deleted' : 'Trashed';

		return [ 'success', "{$action} post {$post_id}." ];
	}

	/**
	 * Gets a list of posts.
	 *
	 * Display posts based on all arguments supported by
	 * [WP_Query()](https://developer.wordpress.org/reference/classes/wp_query/).
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more args to pass to WP_Query.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each post.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each post:
	 *
	 * * ID
	 * * post_title
	 * * post_name
	 * * post_date
	 * * post_status
	 *
	 * These fields are optionally available:
	 *
	 * * post_author
	 * * post_date_gmt
	 * * post_content
	 * * post_excerpt
	 * * comment_status
	 * * ping_status
	 * * post_password
	 * * to_ping
	 * * pinged
	 * * post_modified
	 * * post_modified_gmt
	 * * post_content_filtered
	 * * post_parent
	 * * guid
	 * * menu_order
	 * * post_type
	 * * post_mime_type
	 * * comment_count
	 * * filter
	 * * url
	 *
	 * ## EXAMPLES
	 *
	 *     # List post
	 *     $ wp post list --field=ID
	 *     568
	 *     829
	 *     1329
	 *     1695
	 *
	 *     # List posts in JSON
	 *     $ wp post list --post_type=post --posts_per_page=5 --format=json
	 *     [{"ID":1,"post_title":"Hello world!","post_name":"hello-world","post_date":"2015-06-20 09:00:10","post_status":"publish"},{"ID":1178,"post_title":"Markup: HTML Tags and Formatting","post_name":"markup-html-tags-and-formatting","post_date":"2013-01-11 20:22:19","post_status":"draft"}]
	 *
	 *     # List all pages
	 *     $ wp post list --post_type=page --fields=post_title,post_status
	 *     +-------------+-------------+
	 *     | post_title  | post_status |
	 *     +-------------+-------------+
	 *     | Sample Page | publish     |
	 *     +-------------+-------------+
	 *
	 *     # List ids of all pages and posts
	 *     $ wp post list --post_type=page,post --format=ids
	 *     15 25 34 37 198
	 *
	 *     # List given posts
	 *     $ wp post list --post__in=1,3
	 *     +----+--------------+-------------+---------------------+-------------+
	 *     | ID | post_title   | post_name   | post_date           | post_status |
	 *     +----+--------------+-------------+---------------------+-------------+
	 *     | 3  | Lorem Ipsum  | lorem-ipsum | 2016-06-01 14:34:36 | publish     |
	 *     | 1  | Hello world! | hello-world | 2016-06-01 14:31:12 | publish     |
	 *     +----+--------------+-------------+---------------------+-------------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		$defaults   = [
			'posts_per_page' => -1,
			'post_status'    => 'any',
		];
		$query_args = array_merge( $defaults, $assoc_args );
		$query_args = self::process_csv_arguments_to_arrays( $query_args );
		if ( isset( $query_args['post_type'] ) && 'any' !== $query_args['post_type'] ) {
			$query_args['post_type'] = explode( ',', $query_args['post_type'] );
		}

		if ( 'ids' === $formatter->format ) {
			$query_args['fields'] = 'ids';
			$query                = new WP_Query( $query_args );
			echo implode( ' ', $query->posts );
		} elseif ( 'count' === $formatter->format ) {
			$query_args['fields'] = 'ids';
			$query                = new WP_Query( $query_args );
			$formatter->display_items( $query->posts );
		} else {
			$query = new WP_Query( $query_args );
			$posts = array_map(
				function( $post ) {
						$post->url = get_permalink( $post->ID );
						return $post;
				},
				$query->posts
			);
			$formatter->display_items( $posts );
		}
	}

	/**
	 * Generates some posts.
	 *
	 * Creates a specified number of new posts with dummy data.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many posts to generate?
	 * ---
	 * default: 100
	 * ---
	 *
	 * [--post_type=<type>]
	 * : The type of the generated posts.
	 * ---
	 * default: post
	 * ---
	 *
	 * [--post_status=<status>]
	 * : The status of the generated posts.
	 * ---
	 * default: publish
	 * ---
	 *
	 * [--post_title=<post_title>]
	 * : The post title.
	 * ---
	 * default:
	 * ---
	 *
	 * [--post_author=<login>]
	 * : The author of the generated posts.
	 * ---
	 * default:
	 * ---
	 *
	 * [--post_date=<yyyy-mm-dd-hh-ii-ss>]
	 * : The date of the generated posts. Default: current date
	 *
	 * [--post_date_gmt=<yyyy-mm-dd-hh-ii-ss>]
	 * : The GMT date of the generated posts. Default: value of post_date (or current date if it's not set)
	 *
	 * [--post_content]
	 * : If set, the command reads the post_content from STDIN.
	 *
	 * [--max_depth=<number>]
	 * : For hierarchical post types, generate child posts down to a certain depth.
	 * ---
	 * default: 1
	 * ---
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: progress
	 * options:
	 *   - progress
	 *   - ids
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate posts.
	 *     $ wp post generate --count=10 --post_type=page --post_date=1999-01-04
	 *     Generating posts  100% [================================================] 0:01 / 0:04
	 *
	 *     # Generate posts with fetched content.
	 *     $ curl -N http://loripsum.net/api/5 | wp post generate --post_content --count=10
	 *       % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
	 *                                      Dload  Upload   Total   Spent    Left  Speed
	 *     100  2509  100  2509    0     0    616      0  0:00:04  0:00:04 --:--:--   616
	 *     Generating posts  100% [================================================] 0:01 / 0:04
	 *
	 *     # Add meta to every generated posts.
	 *     $ wp post generate --format=ids | xargs -d ' ' -I % wp post meta add % foo bar
	 *     Success: Added custom field.
	 *     Success: Added custom field.
	 *     Success: Added custom field.
	 */
	public function generate( $args, $assoc_args ) {
		global $wpdb;

		$defaults = [
			'count'         => 100,
			'max_depth'     => 1,
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_author'   => false,
			'post_date'     => false,
			'post_date_gmt' => false,
			'post_content'  => '',
			'post_title'    => '',
		];

		$post_data = array_merge( $defaults, $assoc_args );

		$call_time = current_time( 'mysql' );

		if ( false === $post_data['post_date_gmt'] ) {
			$post_data['post_date_gmt'] = $post_data['post_date'] ?: $call_time;
		}

		if ( false === $post_data['post_date'] ) {
			$post_data['post_date'] = $post_data['post_date_gmt'] ?: $call_time;
		}

		if ( ! post_type_exists( $post_data['post_type'] ) ) {
			WP_CLI::error( "'{$post_data['post_type']}' is not a registered post type." );
		}

		if ( $post_data['post_author'] ) {
			$user_fetcher             = new UserFetcher();
			$post_data['post_author'] = $user_fetcher->get_check( $post_data['post_author'] )->ID;
		}

		if ( Utils\get_flag_value( $assoc_args, 'post_content' ) ) {
			if ( ! EntityUtils::has_stdin() ) {
				WP_CLI::error( 'The parameter `post_content` reads from STDIN.' );
			}

			$post_data['post_content'] = file_get_contents( 'php://stdin' );
		}

		// Get the total number of posts.
		$total = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = %s", $post_data['post_type'] ) );

		$label = ! empty( $post_data['post_title'] )
			? $post_data['post_title']
			: get_post_type_object( $post_data['post_type'] )->labels->singular_name;

		$hierarchical = get_post_type_object( $post_data['post_type'] )->hierarchical;

		$limit = $post_data['count'] + $total;

		$format = Utils\get_flag_value( $assoc_args, 'format', 'progress' );

		$notify = false;
		if ( 'progress' === $format ) {
			$notify = Utils\make_progress_bar( 'Generating posts', $post_data['count'] );
		}

		$previous_post_id = 0;
		$current_depth    = 1;
		$current_parent   = 0;

		for ( $index = $total; $index < $limit; $index++ ) {

			if ( $hierarchical ) {

				if ( $this->maybe_make_child() && $current_depth < $post_data['max_depth'] ) {

					$current_parent = $previous_post_id;
					$current_depth++;

				} elseif ( $this->maybe_reset_depth() ) {

					$current_depth  = 1;
					$current_parent = 0;

				}
			}

			$args = [
				'post_type'     => $post_data['post_type'],
				'post_title'    => ( ! empty( $post_data['post_title'] ) && $index === $total )
					? $label
					: "{$label} {$index}",
				'post_status'   => $post_data['post_status'],
				'post_author'   => $post_data['post_author'],
				'post_parent'   => $current_parent,
				'post_name'     => ! empty( $post_data['post_title'] )
					? sanitize_title( $post_data['post_title'] . ( $index === $total ? '' : "-{$index}" ) )
					: "post-{$index}",
				'post_date'     => $post_data['post_date'],
				'post_date_gmt' => $post_data['post_date_gmt'],
				'post_content'  => $post_data['post_content'],
			];

			$post_id = wp_insert_post( $args, true );
			if ( is_wp_error( $post_id ) ) {
				WP_CLI::warning( $post_id );
			} else {
				$previous_post_id = $post_id;
				if ( 'ids' === $format ) {
					echo $post_id;
					if ( $index < $limit - 1 ) {
						echo ' ';
					}
				}
			}

			if ( 'progress' === $format ) {
				$notify->tick();
			}
		}
		if ( 'progress' === $format ) {
			$notify->finish();
		}
	}

	private function maybe_make_child() {
		// 50% chance of making child post.
		return ( wp_rand( 1, 2 ) === 1 );
	}

	private function maybe_reset_depth() {
		// 10% chance of reseting to root depth,
		return ( wp_rand( 1, 10 ) === 7 );
	}

	/**
	 * Read post content from file or STDIN
	 *
	 * @param string $arg Supplied argument
	 * @return string
	 */
	private function read_from_file_or_stdin( $arg ) {
		if ( '-' !== $arg ) {
			$readfile = $arg;
			if ( ! file_exists( $readfile ) || ! is_file( $readfile ) ) {
				WP_CLI::error( "Unable to read content from '{$readfile}'." );
			}
		} else {
			$readfile = 'php://stdin';
		}
		return file_get_contents( $readfile );
	}

	/**
	 * Resolves post_category arg into an array of category ids.
	 *
	 * @param string $arg Supplied argument.
	 * @return array
	 */
	private function get_category_ids( $arg ) {

		$categories   = explode( ',', $arg );
		$category_ids = [];
		foreach ( $categories as $post_category ) {
			if ( trim( $post_category ) ) {
				if ( is_numeric( $post_category ) && (int) $post_category ) {
					$category_id = category_exists( (int) $post_category );
				} else {
					$category_id = category_exists( $post_category );
				}
				if ( ! $category_id ) {
					WP_CLI::error( "No such post category '{$post_category}'." );
				}
				$category_ids[] = $category_id;
			}
		}
		// If no category ids found, return exploded array for compat with previous WP-CLI versions.
		return $category_ids ?: $categories;
	}

	/**
	 * Get post metadata.
	 *
	 * @param $post_id ID of the post.
	 *
	 * @return array
	 */
	private function get_metadata( $post_id ) {
		$metadata = get_metadata( 'post', $post_id );
		$items    = [];
		foreach ( $metadata as $key => $values ) {
			foreach ( $values as $item_value ) {
				$item_value    = maybe_unserialize( $item_value );
				$items[ $key ] = $item_value;
			}
		}

		return $items;
	}

	/**
	 * Get Categories of a post.
	 *
	 * @param $post_id ID of the post.
	 *
	 * @return array
	 */
	private function get_category( $post_id ) {
		$category_data = get_the_category( $post_id );
		$category_arr  = [];
		foreach ( $category_data as $cat ) {
			array_push( $category_arr, $cat->term_id );
		}

		return $category_arr;
	}

	/**
	 * Get Tags of a post.
	 *
	 * @param $post_id ID of the post.
	 *
	 * @return array
	 */
	private function get_tags( $post_id ) {
		$tag_data = get_the_tags( $post_id );
		$tag_arr  = [];
		if ( $tag_data ) {
			foreach ( $tag_data as $tag ) {
				array_push( $tag_arr, $tag->slug );
			}
		}

		return $tag_arr;
	}

	/**
	 * Verifies whether a post exists.
	 *
	 * Displays a success message if the post does exist.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post to check.
	 *
	 * ## EXAMPLES
	 *
	 *     # The post exists.
	 *     $ wp post exists 1
	 *     Success: Post with ID 1337 exists.
	 *     $ echo $?
	 *     0
	 *
	 *     # The post does not exist.
	 *     $ wp post exists 10000
	 *     $ echo $?
	 *     1
	 */
	public function exists( $args ) {
		if ( $this->fetcher->get( $args[0] ) ) {
			WP_CLI::success( "Post with ID {$args[0]} exists." );
		} else {
			WP_CLI::halt( 1 );
		}
	}
}
