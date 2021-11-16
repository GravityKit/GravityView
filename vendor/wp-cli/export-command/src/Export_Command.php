<?php

use WP_CLI\Utils;

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Changing breaks Phar compat.
define( 'WP_CLI_EXPORT_COMMAND_NO_SPLIT', '-1' );

/**
 * Exports WordPress content to a WXR file.
 *
 * ## EXAMPLES
 *
 *     # Export posts published by the user between given start and end date
 *     $ wp export --dir=/tmp/ --user=admin --post_type=post --start_date=2011-01-01 --end_date=2011-12-31
 *     Starting export process...
 *     Writing to file /tmp/staging.wordpress.2016-05-24.000.xml
 *     Success: All done with export.
 *
 * @package wp-cli
 */
class Export_Command extends WP_CLI_Command {

	/**
	* Initialize the array of arguments that will be eventually be passed to export_wp.
	*
	* @var array
	*/
	public $export_args = [];

	private $stdout;
	private $max_file_size;
	private $wxr_path;

	/**
	 * Exports WordPress content to a WXR file.
	 *
	 * Generates one or more WXR files containing authors, terms, posts,
	 * comments, and attachments. WXR files do not include site configuration
	 * (options) or the attachment files themselves.
	 *
	 * ## OPTIONS
	 *
	 * [--dir=<dirname>]
	 * : Full path to directory where WXR export files should be stored. Defaults
	 * to current working directory.
	 *
	 * [--stdout]
	 * : Output the whole XML using standard output (incompatible with --dir=)
	 *
	 * [--skip_comments]
	 * : Don't include comments in the WXR export file.
	 *
	 * [--max_file_size=<MB>]
	 * : A single export file should have this many megabytes. -1 for unlimited.
	 * ---
	 * default: 15
	 * ---
	 *
	 * ## FILTERS
	 *
	 * [--start_date=<date>]
	 * : Export only posts published after this date, in format YYYY-MM-DD.
	 *
	 * [--end_date=<date>]
	 * : Export only posts published before this date, in format YYYY-MM-DD.
	 *
	 * [--post_type=<post-type>]
	 * : Export only posts with this post_type. Separate multiple post types with a
	 * comma.
	 * ---
	 * default: any
	 * ---
	 *
	 * [--post_type__not_in=<post-type>]
	 * : Export all post types except those identified. Separate multiple post types
	 * with a comma. Defaults to none.
	 *
	 * [--post__in=<pid>]
	 * : Export all posts specified as a comma- or space-separated list of IDs.
	 * Post's attachments won't be exported unless --with_attachments is specified.
	 *
	 * [--with_attachments]
	 * : Force including attachments in case --post__in has been specified.
	 *
	 * [--start_id=<pid>]
	 * : Export only posts with IDs greater than or equal to this post ID.
	 *
	 * [--max_num_posts=<num>]
	 * : Export no more than <num> posts (excluding attachments).
	 *
	 * [--author=<author>]
	 * : Export only posts by this author. Can be either user login or user ID.
	 *
	 * [--category=<name>]
	 * : Export only posts in this category.
	 *
	 * [--post_status=<status>]
	 * : Export only posts with this status.
	 *
	 * [--filename_format=<format>]
	 * : Use a custom format for export filenames. Defaults to '{site}.wordpress.{date}.{n}.xml'.
	 *
	 * ## EXAMPLES
	 *
	 *     # Export posts published by the user between given start and end date
	 *     $ wp export --dir=/tmp/ --user=admin --post_type=post --start_date=2011-01-01 --end_date=2011-12-31
	 *     Starting export process...
	 *     Writing to file /tmp/staging.wordpress.2016-05-24.000.xml
	 *     Success: All done with export.
	 *
	 *     # Export posts by IDs
	 *     $ wp export --dir=/tmp/ --post__in=123,124,125
	 *     Starting export process...
	 *     Writing to file /tmp/staging.wordpress.2016-05-24.000.xml
	 *     Success: All done with export.
	 *
	 *     # Export a random subset of content
	 *     $ wp export --post__in="$(wp post list --post_type=post --orderby=rand --posts_per_page=8 --format=ids)"
	 *     Starting export process...
	 *     Writing to file /var/www/example.com/public_html/staging.wordpress.2016-05-24.000.xml
	 *     Success: All done with export.
	 */
	public function __invoke( $_, $assoc_args ) {
		$defaults = [
			'dir'               => null,
			'stdout'            => false,
			'start_date'        => null,
			'end_date'          => null,
			'post_type'         => null,
			'post_type__not_in' => null,
			'max_num_posts'     => null,
			'author'            => null,
			'category'          => null,
			'post_status'       => null,
			'post__in'          => null,
			'with_attachments'  => true, // or FALSE if user requested some post__in
			'start_id'          => null,
			'skip_comments'     => null,
			'max_file_size'     => 15,
			'filename_format'   => '{site}.wordpress.{date}.{n}.xml',
		];

		if ( ! empty( $assoc_args['stdout'] ) && ( ! empty( $assoc_args['dir'] ) || ! empty( $assoc_args['filename_format'] ) ) ) {
			WP_CLI::error( '--stdout and --dir cannot be used together.' );
		}

		if ( ! empty( $assoc_args['post__in'] ) && empty( $assoc_args['with_attachments'] ) ) {
			$defaults['with_attachments'] = false;
		}

		$assoc_args = wp_parse_args( $assoc_args, $defaults );

		$this->validate_args( $assoc_args );

		$this->export_args['with_attachments'] = Utils\get_flag_value(
			$assoc_args,
			'with_attachments',
			$defaults['with_attachments']
		);

		if ( ! function_exists( 'wp_export' ) ) {
			self::load_export_api();
		}

		if ( ! $this->stdout ) {
			WP_CLI::log( 'Starting export process...' );
		}

		add_action(
			'wp_export_new_file',
			static function ( $file_path ) {
				WP_CLI::log( sprintf( 'Writing to file %s', $file_path ) );
				Utils\wp_clear_object_cache();
			}
		);

		try {
			if ( $this->stdout ) {
				wp_export(
					[
						'filters'     => $this->export_args,
						'writer'      => 'WP_Export_File_Writer',
						'writer_args' => 'php://output',
					]
				);
			} else {
				wp_export(
					[
						'filters'     => $this->export_args,
						'writer'      => 'WP_Export_Split_Files_Writer',
						'writer_args' => [
							'max_file_size'         => $this->max_file_size,
							'destination_directory' => $this->wxr_path,
							'filename_template'     => self::get_filename_template( $assoc_args['filename_format'] ),
						],
					]
				);
			}
		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		if ( ! $this->stdout ) {
			WP_CLI::success( 'All done with export.' );
		}
	}

	private static function get_filename_template( $filename_format ) {
		$sitename = sanitize_key( get_bloginfo( 'name' ) );
		if ( empty( $sitename ) ) {
			$sitename = 'site';
		}
		return str_replace( [ '{site}', '{date}', '{n}' ], [ $sitename, date( 'Y-m-d' ), '%03d' ], $filename_format ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
	}

	public static function load_export_api() {
		require dirname( __DIR__ ) . '/functions.php';
	}

	private function validate_args( $args ) {
		$has_errors = false;

		foreach ( $args as $key => $value ) {
			if ( is_callable( [ $this, 'check_' . $key ] ) ) {
				$result = call_user_func( [ $this, 'check_' . $key ], $value );
				if ( false === $result ) {
					$has_errors = true;
				}
			}
		}

		if ( $args['stdout'] ) {
			$this->wxr_path = null;
			$this->stdout   = true;
		}

		if ( $has_errors ) {
			WP_CLI::halt( 1 );
		}
	}

	private function check_dir( $path ) {
		if ( empty( $path ) ) {
			$path = getcwd();
		} elseif ( ! is_dir( $path ) ) {
			WP_CLI::error( sprintf( "The directory '%s' does not exist.", $path ) );
		} elseif ( ! is_writable( $path ) ) {
			WP_CLI::error( sprintf( "The directory '%s' is not writable.", $path ) );
		}

		$this->wxr_path = trailingslashit( $path );

		return true;
	}

	private function check_start_date( $date ) {
		if ( null === $date ) {
			return true;
		}

		$time = strtotime( $date );
		if ( ! empty( $date ) && ! $time ) {
			WP_CLI::warning( sprintf( 'The start_date %s is invalid.', $date ) );
			return false;
		}
		$this->export_args['start_date'] = date( 'Y-m-d', $time ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		return true;
	}

	private function check_end_date( $date ) {
		if ( null === $date ) {
			return true;
		}

		$time = strtotime( $date );
		if ( ! empty( $date ) && ! $time ) {
			WP_CLI::warning( sprintf( 'The end_date %s is invalid.', $date ) );
			return false;
		}
		$this->export_args['end_date'] = date( 'Y-m-d', $time ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		return true;
	}

	private function check_post_type( $post_type ) {
		if ( null === $post_type || 'any' === $post_type ) {
			return true;
		}

		$post_type  = array_unique( array_filter( explode( ',', $post_type ) ) );
		$post_types = get_post_types();

		foreach ( $post_type as $type ) {
			if ( ! in_array( $type, $post_types, true ) ) {
				WP_CLI::warning(
					sprintf(
						'The post type %s does not exist. Choose "any" or any of these existing post types instead: %s',
						$type,
						implode( ', ', $post_types )
					)
				);
				return false;
			}
		}
		$this->export_args['post_type'] = $post_type;
		return true;
	}

	private function check_post_type__not_in( $post_type ) {
		if ( null === $post_type ) {
			return true;
		}

		$post_type  = array_unique( array_filter( explode( ',', $post_type ) ) );
		$post_types = get_post_types();

		foreach ( $post_type as $type ) {
			if ( ! in_array( $type, $post_types, true ) ) {
				WP_CLI::warning(
					sprintf(
						'The post type %s does not exist. Use any of these existing post types instead: %s',
						$type,
						implode( ', ', $post_types )
					)
				);
				return false;
			}
		}
		$this->export_args['post_type'] = array_diff( $post_types, $post_type );
		return true;
	}

	private function check_post__in( $post__in ) {
		if ( null === $post__in ) {
			return true;
		}

		$separator = false !== stripos( $post__in, ' ' ) ? ' ' : ',';
		$post__in  = array_filter( array_unique( array_map( 'intval', explode( $separator, $post__in ) ) ) );
		if ( empty( $post__in ) ) {
			WP_CLI::warning( 'post__in should be comma-separated post IDs.' );
			return false;
		}
		// New exporter uses a different argument.
		$this->export_args['post_ids'] = $post__in;
		return true;
	}

	private function check_start_id( $start_id ) {
		if ( null === $start_id ) {
			return true;
		}

		$start_id = (int) $start_id;

		// Post IDs must be greater than 0.
		if ( 0 >= $start_id ) {
			WP_CLI::warning( "Invalid start ID: {$start_id}" );
			return false;
		}

		$this->export_args['start_id'] = $start_id;
		return true;
	}

	private function check_author( $author ) {
		if ( null === $author ) {
			return true;
		}

		// phpcs:ignore WordPress.WP.DeprecatedFunctions.get_users_of_blogFound -- Fallback.
		$authors = function_exists( 'get_users' ) ? get_users() : get_users_of_blog();
		if ( empty( $authors ) || is_wp_error( $authors ) ) {
			WP_CLI::warning( 'Could not find any authors in this blog.' );
			return false;
		}
		$hit = false;
		foreach ( $authors as $user ) {
			if ( $hit ) {
				break;
			}
			if ( (int) $author === $user->ID || $author === $user->user_login ) {
				$hit = $user->ID;
			}
		}
		if ( false === $hit ) {
			$authors_nice = [];
			foreach ( $authors as $_author ) {
				$authors_nice[] = sprintf( '%s (%s)', $_author->user_login, $_author->display_name );
			}
			WP_CLI::warning( sprintf( 'Could not find a matching author for %s. The following authors exist: %s', $author, implode( ', ', $authors_nice ) ) );
			return false;
		}

		$this->export_args['author'] = $hit;
		return true;
	}

	private function check_max_num_posts( $num ) {
		if ( null !== $num && ( ! is_numeric( $num ) || $num <= 0 ) ) {
			WP_CLI::warning( 'max_num_posts should be a positive integer.' );
			return false;
		}

		$this->export_args['max_num_posts'] = (int) $num;

		return true;
	}

	private function check_category( $category ) {
		if ( null === $category ) {
			return true;
		}

		$term = category_exists( $category );
		if ( empty( $term ) || is_wp_error( $term ) ) {
			WP_CLI::warning( sprintf( 'Could not find a category matching %s.', $category ) );
			return false;
		}
		$this->export_args['category'] = $category;
		return true;
	}

	private function check_post_status( $status ) {
		if ( null === $status ) {
			return true;
		}

		$stati = get_post_statuses();
		if ( empty( $stati ) || is_wp_error( $stati ) ) {
			WP_CLI::warning( 'Could not find any post stati.' );
			return false;
		}

		if ( ! isset( $stati[ $status ] ) ) {
			WP_CLI::warning( sprintf( 'Could not find a post_status matching %s. Here is a list of available stati: %s', $status, implode( ', ', array_keys( $stati ) ) ) );
			return false;
		}
		$this->export_args['status'] = $status;
		return true;
	}

	private function check_skip_comments( $skip ) {
		if ( null === $skip ) {
			return true;
		}

		if ( 0 !== (int) $skip && 1 !== (int) $skip ) {
			WP_CLI::warning( 'skip_comments needs to be 0 (no) or 1 (yes).' );
			return false;
		}
		$this->export_args['skip_comments'] = $skip;
		return true;
	}

	private function check_max_file_size( $size ) {
		if ( ! is_numeric( $size ) ) {
			WP_CLI::warning( 'max_file_size should be numeric.' );
			return false;
		}

		$this->max_file_size = $size;

		return true;
	}
}
