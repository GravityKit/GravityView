<?php

class Import_Command extends WP_CLI_Command {

	public $processed_posts = array();

	/**
	 * Imports content from a given WXR file.
	 *
	 * Provides a command line interface to the WordPress Importer plugin, for
	 * performing data migrations.
	 *
	 * ## OPTIONS
	 *
	 * <file>...
	 * : Path to one or more valid WXR files for importing. Directories are also accepted.
	 *
	 * --authors=<authors>
	 * : How the author mapping should be handled. Options are 'create', 'mapping.csv', or 'skip'. The first will create any non-existent users from the WXR file. The second will read author mapping associations from a CSV, or create a CSV for editing if the file path doesn't exist. The CSV requires two columns, and a header row like "old_user_login,new_user_login". The last option will skip any author mapping.
	 *
	 * [--skip=<data-type>]
	 * : Skip importing specific data. Supported options are: 'attachment' and 'image_resize' (skip time-consuming thumbnail generation).
	 *
	 * ## EXAMPLES
	 *
	 *     # Import content from a WXR file
	 *     $ wp import example.wordpress.2016-06-21.xml --authors=create
	 *     Starting the import process...
	 *     Processing post #1 ("Hello world!") (post_type: post)
	 *     -- 1 of 1
	 *     -- Tue, 21 Jun 2016 05:31:12 +0000
	 *     -- Imported post as post_id #1
	 *     Success: Finished importing from 'example.wordpress.2016-06-21.xml' file.
	 */
	public function __invoke( $args, $assoc_args ) {
		$defaults   = array(
			'authors' => null,
			'skip'    => array(),
		);
		$assoc_args = wp_parse_args( $assoc_args, $defaults );

		if ( ! is_array( $assoc_args['skip'] ) ) {
			$assoc_args['skip'] = explode( ',', $assoc_args['skip'] );
		}

		$importer = $this->is_importer_available();
		if ( is_wp_error( $importer ) ) {
			WP_CLI::error( $importer );
		}

		$this->add_wxr_filters();

		WP_CLI::log( 'Starting the import process...' );

		$new_args = array();
		foreach ( $args as $arg ) {
			if ( is_dir( $arg ) ) {
				$dir   = WP_CLI\Utils\trailingslashit( $arg );
				$files = glob( $dir . '*.wxr' );
				if ( ! empty( $files ) ) {
					$new_args = array_merge( $new_args, $files );
				}

				$files = glob( $dir . '*.xml' );
				if ( ! empty( $files ) ) {
					$new_args = array_merge( $new_args, $files );
				}
			} else {
				if ( file_exists( $arg ) ) {
					$new_args[] = $arg;
				}
			}
		}
		$args = $new_args;

		foreach ( $args as $file ) {
			if ( ! is_readable( $file ) ) {
				WP_CLI::warning( "Can't read '$file' file." );
			}

			$ret = $this->import_wxr( $file, $assoc_args );

			if ( is_wp_error( $ret ) ) {
				WP_CLI::error( $ret );
			} else {
				WP_CLI::log( '' ); // WXR import ends with HTML, so make sure message is on next line
				WP_CLI::success( "Finished importing from '$file' file." );
			}
		}
	}

	/**
	 * Imports a WXR file.
	 */
	private function import_wxr( $file, $args ) {

		$wp_import                  = new WP_Import();
		$wp_import->processed_posts = $this->processed_posts;
		$import_data                = $wp_import->parse( $file );
		if ( is_wp_error( $import_data ) ) {
			return $import_data;
		}

		// Prepare the data to be used in process_author_mapping();
		$wp_import->get_authors_from_import( $import_data );

		// We no longer need the original data, so unset to avoid using excess
		// memory.
		unset( $import_data );

		$author_data = array();
		foreach ( $wp_import->authors as $wxr_author ) {
			$author = new \stdClass();
			// Always in the WXR
			$author->user_login = $wxr_author['author_login'];

			// Should be in the WXR; no guarantees
			if ( isset( $wxr_author['author_email'] ) ) {
				$author->user_email = $wxr_author['author_email'];
			}
			if ( isset( $wxr_author['author_display_name'] ) ) {
				$author->display_name = $wxr_author['author_display_name'];
			}
			if ( isset( $wxr_author['author_first_name'] ) ) {
				$author->first_name = $wxr_author['author_first_name'];
			}
			if ( isset( $wxr_author['author_last_name'] ) ) {
				$author->last_name = $wxr_author['author_last_name'];
			}

			$author_data[] = $author;
		}

		// Build the author mapping
		$author_mapping = $this->process_author_mapping( $args['authors'], $author_data );
		if ( is_wp_error( $author_mapping ) ) {
			return $author_mapping;
		}

		$author_in  = wp_list_pluck( $author_mapping, 'old_user_login' );
		$author_out = wp_list_pluck( $author_mapping, 'new_user_login' );
		unset( $author_mapping, $author_data );

		// $user_select needs to be an array of user IDs
		$user_select         = array();
		$invalid_user_select = array();
		foreach ( $author_out as $author_login ) {
			$user = get_user_by( 'login', $author_login );
			if ( $user ) {
				$user_select[] = $user->ID;
			} else {
				$invalid_user_select[] = $author_login;
			}
		}
		if ( ! empty( $invalid_user_select ) ) {
			return new WP_Error( 'invalid-author-mapping', sprintf( 'These user_logins are invalid: %s', implode( ',', $invalid_user_select ) ) );
		}

		unset( $author_out );

		// Drive the import
		$wp_import->fetch_attachments = ! in_array( 'attachment', $args['skip'], true );

		$_GET  = array(
			'import' => 'wordpress',
			'step'   => 2,
		);
		$_POST = array(
			'imported_authors'  => $author_in,
			'user_map'          => $user_select,
			'fetch_attachments' => $wp_import->fetch_attachments,
		);

		if ( in_array( 'image_resize', $args['skip'], true ) ) {
			add_filter( 'intermediate_image_sizes_advanced', array( $this, 'filter_set_image_sizes' ) );
		}

		$GLOBALS['wpcli_import_current_file'] = basename( $file );
		$wp_import->import( $file );
		$this->processed_posts += $wp_import->processed_posts;

		return true;
	}

	public function filter_set_image_sizes( $sizes ) {
		// Return null here to prevent the core image resizing logic from running.
		return null;
	}

	/**
	 * Defines useful verbosity filters for the WXR importer.
	 */
	private function add_wxr_filters() {

		add_filter(
			'wp_import_posts',
			function( $posts ) {
				global $wpcli_import_counts;
				$wpcli_import_counts['current_post'] = 0;
				$wpcli_import_counts['total_posts']  = count( $posts );
				return $posts;
			},
			10
		);

		add_filter(
			'wp_import_post_comments',
			function( $comments, $post_id, $post ) {
				global $wpcli_import_counts;
				$wpcli_import_counts['current_comment'] = 0;
				$wpcli_import_counts['total_comments']  = count( $comments );
				return $comments;
			},
			10,
			3
		);

		add_filter(
			'wp_import_post_data_raw',
			function( $post ) {
				global $wpcli_import_counts, $wpcli_import_current_file;

				$wpcli_import_counts['current_post']++;
				WP_CLI::log( '' );
				WP_CLI::log( '' );
				WP_CLI::log( sprintf( 'Processing post #%d ("%s") (post_type: %s)', $post['post_id'], $post['post_title'], $post['post_type'] ) );
				WP_CLI::log( sprintf( '-- %s of %s (in file %s)', number_format( $wpcli_import_counts['current_post'] ), number_format( $wpcli_import_counts['total_posts'] ), $wpcli_import_current_file ) );
				WP_CLI::log( '-- ' . date( 'r' ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

				return $post;
			}
		);

		add_action(
			'wp_import_insert_post',
			function( $post_id, $original_post_id, $post, $postdata ) {
				global $wpcli_import_counts;
				if ( is_wp_error( $post_id ) ) {
					WP_CLI::warning( '-- Error importing post: ' . $post_id->get_error_code() );
				} else {
					WP_CLI::log( "-- Imported post as post_id #{$post_id}" );
				}

				if ( 0 === ( $wpcli_import_counts['current_post'] % 500 ) ) {
					WP_CLI\Utils\wp_clear_object_cache();
					WP_CLI::log( '-- Cleared object cache.' );
				}

			},
			10,
			4
		);

		add_action(
			'wp_import_insert_term',
			function( $t, $import_term, $post_id, $post ) {
				WP_CLI::log( "-- Created term \"{$import_term['name']}\"" );
			},
			10,
			4
		);

		add_action(
			'wp_import_set_post_terms',
			function( $tt_ids, $term_ids, $taxonomy, $post_id, $post ) {
				WP_CLI::log( '-- Added terms (' . implode( ',', $term_ids ) . ") for taxonomy \"{$taxonomy}\"" );
			},
			10,
			5
		);

		add_action(
			'wp_import_insert_comment',
			function( $comment_id, $comment, $comment_post_id, $post ) {
				global $wpcli_import_counts;
				$wpcli_import_counts['current_comment']++;
				WP_CLI::log( sprintf( '-- Added comment #%d (%s of %s)', $comment_id, number_format( $wpcli_import_counts['current_comment'] ), number_format( $wpcli_import_counts['total_comments'] ) ) );
			},
			10,
			4
		);

		add_action(
			'import_post_meta',
			function( $post_id, $key, $value ) {
				WP_CLI::log( "-- Added post_meta $key" );
			},
			10,
			3
		);

	}

	/**
	 * Determines whether the requested importer is available.
	 */
	private function is_importer_available() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( class_exists( 'WP_Import' ) ) {
			return true;
		}

		$plugins            = get_plugins();
		$wordpress_importer = 'wordpress-importer/wordpress-importer.php';
		if ( array_key_exists( $wordpress_importer, $plugins ) ) {
			$error_msg = "WordPress Importer needs to be activated. Try 'wp plugin activate wordpress-importer'.";
		} else {
			$error_msg = "WordPress Importer needs to be installed. Try 'wp plugin install wordpress-importer --activate'.";
		}

		return new WP_Error( 'importer-missing', $error_msg );
	}

	/**
	 * Processes how the authors should be mapped
	 *
	 * @param string            $authors_arg      The `--author` argument originally passed to command
	 * @param array             $author_data      An array of WP_User-esque author objects
	 * @return array|WP_Error   $author_mapping   Author mapping array if successful, WP_Error if something bad happened
	 */
	private function process_author_mapping( $authors_arg, $author_data ) {

		// Provided an author mapping file (method checks validity)
		if ( file_exists( $authors_arg ) ) {
			return $this->read_author_mapping_file( $authors_arg );
		}

		// Provided a file reference, but the file doesn't yet exist
		if ( false !== stripos( $authors_arg, '.csv' ) ) {
			return $this->create_author_mapping_file( $authors_arg, $author_data );
		}

		switch ( $authors_arg ) {
			// Create authors if they don't yet exist; maybe match on email or user_login
			case 'create':
				return $this->create_authors_for_mapping( $author_data );

			// Skip any sort of author mapping
			case 'skip':
				return array();

			default:
				return new WP_Error( 'invalid-argument', "'authors' argument is invalid." );
		}
	}

	/**
	 * Reads an author mapping file.
	 */
	private function read_author_mapping_file( $file ) {
		$author_mapping = array();

		foreach ( new \WP_CLI\Iterators\CSV( $file ) as $i => $author ) {
			if ( ! array_key_exists( 'old_user_login', $author ) || ! array_key_exists( 'new_user_login', $author ) ) {
				return new WP_Error( 'invalid-author-mapping', "Author mapping file isn't properly formatted." );
			}

			$author_mapping[] = $author;
		}

		return $author_mapping;
	}

	/**
	 * Creates an author mapping file, based on provided author data.
	 *
	 * @return WP_Error      The file was just now created, so some action needs to be taken
	 */
	private function create_author_mapping_file( $file, $author_data ) {

		if ( touch( $file ) ) {
			$author_mapping = array();
			foreach ( $author_data as $author ) {
				$author_mapping[] = array(
					'old_user_login' => $author->user_login,
					'new_user_login' => $this->suggest_user( $author->user_login, $author->user_email ),
				);
			}
			$file_resource = fopen( $file, 'w' );
			\WP_CLI\utils\write_csv( $file_resource, $author_mapping, array( 'old_user_login', 'new_user_login' ) );
			return new WP_Error( 'author-mapping-error', sprintf( 'Please update author mapping file before continuing: %s', $file ) );
		} else {
			return new WP_Error( 'author-mapping-error', "Couldn't create author mapping file." );
		}
	}

	/**
	 * Creates users if they don't exist, and build an author mapping file.
	 */
	private function create_authors_for_mapping( $author_data ) {

		$author_mapping = array();
		foreach ( $author_data as $author ) {

			if ( isset( $author->user_email ) ) {
				$user = get_user_by( 'email', $author->user_email );
				if ( $user instanceof WP_User ) {
					$author_mapping[] = array(
						'old_user_login' => $author->user_login,
						'new_user_login' => $user->user_login,
					);
					continue;
				}
			}

			$user = get_user_by( 'login', $author->user_login );
			if ( $user instanceof WP_User ) {
				$author_mapping[] = array(
					'old_user_login' => $author->user_login,
					'new_user_login' => $user->user_login,
				);
				continue;
			}

			$user = array(
				'user_login' => '',
				'user_email' => '',
				'user_pass'  => wp_generate_password(),
			);
			$user = array_merge( $user, (array) $author );

			$user_id = wp_insert_user( $user );
			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			$user             = get_user_by( 'id', $user_id );
			$author_mapping[] = array(
				'old_user_login' => $author->user_login,
				'new_user_login' => $user->user_login,
			);
		}
		return $author_mapping;

	}

	/**
	 * Suggests a blog user based on the levenshtein distance.
	 */
	private function suggest_user( $author_user_login, $author_user_email = '' ) {

		if ( ! isset( $this->blog_users ) ) {
			$this->blog_users = get_users();
		}

		$shortest    = -1;
		$shortestavg = array();

		$threshold = floor( ( strlen( $author_user_login ) / 100 ) * 10 ); // 10 % of the strlen are valid
		$closest   = '';
		foreach ( $this->blog_users as $user ) {
			// Before we resort to an algorithm, let's try for an exact match
			if ( $author_user_email && $user->user_email === $author_user_email ) {
				return $user->user_login;
			}

			$levs        = array();
			$levs[]      = levenshtein( $author_user_login, $user->display_name );
			$levs[]      = levenshtein( $author_user_login, $user->user_login );
			$levs[]      = levenshtein( $author_user_login, $user->user_email );
			$email_parts = explode( '@', $user->user_email );
			$email_login = array_shift( $email_parts );
			$levs[]      = levenshtein( $author_user_login, $email_login );
			rsort( $levs );
			$lev = array_pop( $levs );
			if ( 0 === $lev ) {
				$closest  = $user->user_login;
				$shortest = 0;
				break;
			}

			if ( ( $lev <= $shortest || $shortest < 0 ) && $lev <= $threshold ) {
				$closest  = $user->user_login;
				$shortest = $lev;
			}
			$shortestavg[] = $lev;
		}
		// in case all usernames have a common pattern
		if ( $shortest > ( array_sum( $shortestavg ) / count( $shortestavg ) ) ) {
			return '';
		}

		return $closest;
	}

}
