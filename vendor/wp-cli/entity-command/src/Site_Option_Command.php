<?php

use WP_CLI\Entity\RecursiveDataStructureTraverser;
use WP_CLI\Formatter;
use WP_CLI\Utils;
use WP_CLI\Entity\Utils as EntityUtils;

/**
 * Adds, updates, deletes, and lists site options in a multisite installation.
 *
 * ## EXAMPLES
 *
 *     # Get site registration
 *     $ wp site option get registration
 *     none
 *
 *     # Add site option
 *     $ wp site option add my_option foobar
 *     Success: Added 'my_option' site option.
 *
 *     # Update site option
 *     $ wp site option update my_option '{"foo": "bar"}' --format=json
 *     Success: Updated 'my_option' site option.
 *
 *     # Delete site option
 *     $ wp site option delete my_option
 *     Success: Deleted 'my_option' site option.
 */
class Site_Option_Command extends WP_CLI_Command {

	/**
	 * Gets a site option.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Key for the site option.
	 *
	 * [--format=<format>]
	 * : Get value in a particular format.
	 * ---
	 * default: var_export
	 * options:
	 *   - var_export
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get site upload filetypes
	 *     $ wp site option get upload_filetypes
	 *     jpg jpeg png gif mov avi mpg
	 */
	public function get( $args, $assoc_args ) {
		list( $key ) = $args;

		$value = get_site_option( $key );

		if ( false === $value ) {
			WP_CLI::halt( 1 );
		}

		WP_CLI::print_value( $value, $assoc_args );
	}

	/**
	 * Adds a site option.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The name of the site option to add.
	 *
	 * [<value>]
	 * : The value of the site option to add. If ommited, the value is read from STDIN.
	 *
	 * [--format=<format>]
	 * : The serialization format for the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Create a site option by reading a JSON file
	 *     $ wp site option add my_option --format=json < config.json
	 *     Success: Added 'my_option' site option.
	 */
	public function add( $args, $assoc_args ) {
		$key = $args[0];

		$value = WP_CLI::get_value_from_arg_or_stdin( $args, 1 );
		$value = WP_CLI::read_value( $value, $assoc_args );

		if ( ! add_site_option( $key, $value ) ) {
			WP_CLI::error( "Could not add site option '{$key}'. Does it already exist?" );
		} else {
			WP_CLI::success( "Added '{$key}' site option." );
		}
	}

	/**
	 * Lists site options.
	 *
	 * ## OPTIONS
	 *
	 * [--search=<pattern>]
	 * : Use wildcards ( * and ? ) to match option name.
	 *
	 * [--site_id=<id>]
	 * : Limit options to those of a particular site id.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : The serialization format for the value. total_bytes displays the total size of matching options in bytes.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - count
	 *   - yaml
	 *   - total_bytes
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * This field will be displayed by default for each matching option:
	 *
	 * * meta_key
	 * * meta_value
	 *
	 * These fields are optionally available:
	 *
	 * * meta_id
	 * * site_id
	 * * size_bytes
	 *
	 * ## EXAMPLES
	 *
	 *     # List all site options begining with "i2f_"
	 *     $ wp site option list --search="i2f_*"
	 *     +-------------+--------------+
	 *     | meta_key    | meta_value   |
	 *     +-------------+--------------+
	 *     | i2f_version | 0.1.0        |
	 *     +-------------+--------------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {

		global $wpdb;
		$pattern    = '%';
		$fields     = [ 'meta_key', 'meta_value' ];
		$size_query = ',LENGTH(meta_value) AS `size_bytes`';

		if ( isset( $assoc_args['search'] ) ) {
			$pattern = self::esc_like( $assoc_args['search'] );
			// substitute wildcards
			$pattern = str_replace( '*', '%', $pattern );
			$pattern = str_replace( '?', '_', $pattern );
		}

		if ( isset( $assoc_args['fields'] ) ) {
			$fields = explode( ',', $assoc_args['fields'] );
		}

		if ( Utils\get_flag_value( $assoc_args, 'format' ) === 'total_bytes' ) {
			$fields     = [ 'size_bytes' ];
			$size_query = ',SUM(LENGTH(meta_value)) AS `size_bytes`';
		}

		$query = $wpdb->prepare(
			'SELECT `meta_id`, `site_id`, `meta_key`,`meta_value`'
				. $size_query // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Hard-coded partial query without user input.
				. " FROM `$wpdb->sitemeta` WHERE `meta_key` LIKE %s",
			$pattern
		);

		$site_id = Utils\get_flag_value( $assoc_args, 'site_id' );
		if ( $site_id ) {
			$query .= $wpdb->prepare( ' AND site_id=%d', $site_id );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is already prepared above.
		$results = $wpdb->get_results( $query );

		if ( Utils\get_flag_value( $assoc_args, 'format' ) === 'total_bytes' ) {
			WP_CLI::line( $results[0]->size_bytes );
		} else {
			$formatter = new Formatter(
				$assoc_args,
				$fields
			);
			$formatter->display_items( $results );
		}
	}

	/**
	 * Updates a site option.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The name of the site option to update.
	 *
	 * [<value>]
	 * : The new value. If ommited, the value is read from STDIN.
	 *
	 * [--format=<format>]
	 * : The serialization format for the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Update a site option by reading from a file
	 *     $ wp site option update my_option < value.txt
	 *     Success: Updated 'my_option' site option.
	 *
	 * @alias set
	 */
	public function update( $args, $assoc_args ) {
		$key = $args[0];

		$value = WP_CLI::get_value_from_arg_or_stdin( $args, 1 );
		$value = WP_CLI::read_value( $value, $assoc_args );

		$value     = sanitize_option( $key, $value );
		$old_value = sanitize_option( $key, get_site_option( $key ) );

		if ( $value === $old_value ) {
			WP_CLI::success( "Value passed for '{$key}' site option is unchanged." );
		} else {
			if ( update_site_option( $key, $value ) ) {
				WP_CLI::success( "Updated '{$key}' site option." );
			} else {
				WP_CLI::error( "Could not update site option '{$key}'." );
			}
		}
	}

	/**
	 * Deletes a site option.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Key for the site option.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site option delete my_option
	 *     Success: Deleted 'my_option' site option.
	 */
	public function delete( $args ) {
		list( $key ) = $args;

		if ( ! delete_site_option( $key ) ) {
			WP_CLI::error( "Could not delete '{$key}' site option. Does it exist?" );
		} else {
			WP_CLI::success( "Deleted '{$key}' site option." );
		}
	}

	/**
	 * Gets a nested value from an option.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The option name.
	 *
	 * <key-path>...
	 * : The name(s) of the keys within the value to locate the value to pluck.
	 *
	 * [--format=<format>]
	 * : The output format of the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 *   - yaml
	 */
	public function pluck( $args, $assoc_args ) {
		list( $key ) = $args;

		$value = get_site_option( $key );

		if ( false === $value ) {
			WP_CLI::halt( 1 );
		}

		$key_path = array_map(
			function( $key ) {
				if ( is_numeric( $key ) && ( (string) intval( $key ) === $key ) ) {
					return (int) $key;
				}
					return $key;
			},
			array_slice( $args, 1 )
		);

		$traverser = new RecursiveDataStructureTraverser( $value );

		try {
			$value = $traverser->get( $key_path );
		} catch ( Exception $exception ) {
			die( 1 );
		}

		WP_CLI::print_value( $value, $assoc_args );
	}

	/**
	 * Updates a nested value in an option.
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : Patch action to perform.
	 * ---
	 * options:
	 *   - insert
	 *   - update
	 *   - delete
	 * ---
	 *
	 * <key>
	 * : The option name.
	 *
	 * <key-path>...
	 * : The name(s) of the keys within the value to locate the value to patch.
	 *
	 * [<value>]
	 * : The new value. If omitted, the value is read from STDIN.
	 *
	 * [--format=<format>]
	 * : The serialization format for the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 * ---
	 */
	public function patch( $args, $assoc_args ) {
		list( $action, $key ) = $args;
		$key_path             = array_map(
			function( $key ) {
				if ( is_numeric( $key ) && ( (string) intval( $key ) === $key ) ) {
					return (int) $key;
				}
					return $key;
			},
			array_slice( $args, 2 )
		);

		if ( 'delete' === $action ) {
			$patch_value = null;
		} else {
			$stdin_value = EntityUtils::has_stdin()
				? trim( WP_CLI::get_value_from_arg_or_stdin( $args, -1 ) )
				: null;
			$patch_value = ! empty( $stdin_value )
				? WP_CLI::read_value( $stdin_value, $assoc_args )
				: WP_CLI::read_value( array_pop( $key_path ), $assoc_args );
		}

		/* Need to make a copy of $current_value here as it is modified by reference */
		$old_value     = sanitize_option( $key, get_site_option( $key ) );
		$current_value = $old_value;
		if ( is_object( $current_value ) ) {
			$old_value = clone $current_value;
		}

		$traverser = new RecursiveDataStructureTraverser( $current_value );

		try {
			$traverser->$action( $key_path, $patch_value );
		} catch ( Exception $exception ) {
			WP_CLI::error( $exception->getMessage() );
		}

		$patched_value = sanitize_option( $key, $traverser->value() );

		if ( $patched_value === $old_value ) {
			WP_CLI::success( "Value passed for '{$key}' site option is unchanged." );
		} else {
			if ( update_site_option( $key, $patched_value ) ) {
				WP_CLI::success( "Updated '{$key}' site option." );
			} else {
				WP_CLI::error( "Could not update site option '{$key}'." );
			}
		}
	}

	private static function esc_like( $old ) {
		global $wpdb;

		// Remove notices in 4.0 and support backwards compatibility
		if ( method_exists( $wpdb, 'esc_like' ) ) {
			// 4.0
			$old = $wpdb->esc_like( $old );
		} else {
			// phpcs:ignore WordPress.WP.DeprecatedFunctions.like_escapeFound -- called in WordPress 3.9 or less.
			$old = like_escape( esc_sql( $old ) );
		}

		return $old;
	}
}
