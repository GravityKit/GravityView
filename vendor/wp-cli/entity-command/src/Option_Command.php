<?php

use WP_CLI\Entity\RecursiveDataStructureTraverser;
use WP_CLI\Formatter;
use WP_CLI\Utils;
use WP_CLI\Entity\Utils as EntityUtils;

/**
 * Retrieves and sets site options, including plugin and WordPress settings.
 *
 * See the [Plugin Settings API](https://developer.wordpress.org/plugins/settings/settings-api/) and the [Theme Options](https://developer.wordpress.org/themes/customize-api/) for more information on adding customized options.
 *
 * ## EXAMPLES
 *
 *     # Get site URL.
 *     $ wp option get siteurl
 *     http://example.com
 *
 *     # Add option.
 *     $ wp option add my_option foobar
 *     Success: Added 'my_option' option.
 *
 *     # Update option.
 *     $ wp option update my_option '{"foo": "bar"}' --format=json
 *     Success: Updated 'my_option' option.
 *
 *     # Delete option.
 *     $ wp option delete my_option
 *     Success: Deleted 'my_option' option.
 *
 * @package wp-cli
 */
class Option_Command extends WP_CLI_Command {

	/**
	 * Gets the value for an option.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Key for the option.
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
	 *     # Get option.
	 *     $ wp option get home
	 *     http://example.com
	 *
	 *     # Get blog description.
	 *     $ wp option get blogdescription
	 *     A random blog description
	 *
	 *     # Get blog name
	 *     $ wp option get blogname
	 *     A random blog name
	 *
	 *     # Get admin email.
	 *     $ wp option get admin_email
	 *     someone@example.com
	 *
	 *     # Get option in JSON format.
	 *     $ wp option get active_plugins --format=json
	 *     {"0":"dynamically-dynamic-sidebar\/dynamically-dynamic-sidebar.php","1":"monster-widget\/monster-widget.php","2":"show-current-template\/show-current-template.php","3":"theme-check\/theme-check.php","5":"wordpress-importer\/wordpress-importer.php"}
	 */
	public function get( $args, $assoc_args ) {
		list( $key ) = $args;

		$value = get_option( $key );

		if ( false === $value ) {
			WP_CLI::error( "Could not get '{$key}' option. Does it exist?" );
		}

		WP_CLI::print_value( $value, $assoc_args );
	}

	/**
	 * Adds a new option value.
	 *
	 * Errors if the option already exists.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The name of the option to add.
	 *
	 * [<value>]
	 * : The value of the option to add. If ommited, the value is read from STDIN.
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
	 * [--autoload=<autoload>]
	 * : Should this option be automatically loaded.
	 * ---
	 * options:
	 *   - 'yes'
	 *   - 'no'
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Create an option by reading a JSON file.
	 *     $ wp option add my_option --format=json < config.json
	 *     Success: Added 'my_option' option.
	 */
	public function add( $args, $assoc_args ) {
		$key = $args[0];

		$value = WP_CLI::get_value_from_arg_or_stdin( $args, 1 );
		$value = WP_CLI::read_value( $value, $assoc_args );

		if ( Utils\get_flag_value( $assoc_args, 'autoload' ) === 'no' ) {
			$autoload = 'no';
		} else {
			$autoload = 'yes';
		}

		if ( ! add_option( $key, $value, '', $autoload ) ) {
			WP_CLI::error( "Could not add option '{$key}'. Does it already exist?" );
		} else {
			WP_CLI::success( "Added '{$key}' option." );
		}
	}

	/**
	 * Lists options and their values.
	 *
	 * ## OPTIONS
	 *
	 * [--search=<pattern>]
	 * : Use wildcards ( * and ? ) to match option name.
	 *
	 * [--exclude=<pattern>]
	 * : Pattern to exclude. Use wildcards ( * and ? ) to match option name.
	 *
	 * [--autoload=<value>]
	 * : Match only autoload options when value is on, and only not-autoload option when off.
	 *
	 * [--transients]
	 * : List only transients. Use `--no-transients` to ignore all transients.
	 *
	 * [--unserialize]
	 * : Unserialize option values in output.
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
	 * [--orderby=<fields>]
	 * : Set orderby which field.
	 * ---
	 * default: option_id
	 * options:
	 *  - option_id
	 *  - option_name
	 *  - option_value
	 * ---
	 *
	 * [--order=<order>]
	 * : Set ascending or descending order.
	 * ---
	 * default: asc
	 * options:
	 *  - asc
	 *  - desc
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * This field will be displayed by default for each matching option:
	 *
	 * * option_name
	 * * option_value
	 *
	 * These fields are optionally available:
	 *
	 * * autoload
	 * * size_bytes
	 *
	 * ## EXAMPLES
	 *
	 *     # Get the total size of all autoload options.
	 *     $ wp option list --autoload=on --format=total_bytes
	 *     33198
	 *
	 *     # Find biggest transients.
	 *     $ wp option list --search="*_transient_*" --fields=option_name,size_bytes | sort -n -k 2 | tail
	 *     option_name size_bytes
	 *     _site_transient_timeout_theme_roots 10
	 *     _site_transient_theme_roots 76
	 *     _site_transient_update_themes   181
	 *     _site_transient_update_core 808
	 *     _site_transient_update_plugins  6645
	 *
	 *     # List all options beginning with "i2f_".
	 *     $ wp option list --search="i2f_*"
	 *     +-------------+--------------+
	 *     | option_name | option_value |
	 *     +-------------+--------------+
	 *     | i2f_version | 0.1.0        |
	 *     +-------------+--------------+
	 *
	 *     # Delete all options beginning with "theme_mods_".
	 *     $ wp option list --search="theme_mods_*" --field=option_name | xargs -I % wp option delete %
	 *     Success: Deleted 'theme_mods_twentysixteen' option.
	 *     Success: Deleted 'theme_mods_twentyfifteen' option.
	 *     Success: Deleted 'theme_mods_twentyfourteen' option.
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {

		global $wpdb;
		$pattern        = '%';
		$exclude        = '';
		$fields         = array( 'option_name', 'option_value' );
		$size_query     = ',LENGTH(option_value) AS `size_bytes`';
		$autoload_query = '';

		if ( isset( $assoc_args['search'] ) ) {
			$pattern = self::esc_like( $assoc_args['search'] );
			// substitute wildcards
			$pattern = str_replace( '*', '%', $pattern );
			$pattern = str_replace( '?', '_', $pattern );
		}

		if ( isset( $assoc_args['exclude'] ) ) {
			$exclude = self::esc_like( $assoc_args['exclude'] );
			$exclude = str_replace( '*', '%', $exclude );
			$exclude = str_replace( '?', '_', $exclude );
		}

		if ( isset( $assoc_args['fields'] ) ) {
			$fields = explode( ',', $assoc_args['fields'] );
		}

		if ( Utils\get_flag_value( $assoc_args, 'format' ) === 'total_bytes' ) {
			$fields     = array( 'size_bytes' );
			$size_query = ',SUM(LENGTH(option_value)) AS `size_bytes`';
		}

		if ( isset( $assoc_args['autoload'] ) ) {
			if ( 'on' === $assoc_args['autoload'] ) {
				$autoload_query = " AND autoload='yes'";
			} elseif ( 'off' === $assoc_args['autoload'] ) {
				$autoload_query = " AND autoload='no'";
			} else {
				WP_CLI::error( "Value of '--autoload' should be on or off." );
			}
		}

		// By default we don't want to display transients.
		$show_transients = Utils\get_flag_value( $assoc_args, 'transients', false );

		if ( $show_transients ) {
			$transients_query = " AND option_name LIKE '\_transient\_%'
			OR option_name LIKE '\_site\_transient\_%'";
		} else {
			$transients_query = " AND option_name NOT LIKE '\_transient\_%'
			AND option_name NOT LIKE '\_site\_transient\_%'";
		}

		$where = '';
		if ( $pattern ) {
			$where .= $wpdb->prepare( 'WHERE `option_name` LIKE %s', $pattern );
		}

		if ( $exclude ) {
			$where .= $wpdb->prepare( ' AND `option_name` NOT LIKE %s', $exclude );
		}
		$where .= $autoload_query . $transients_query;

		// phpcs:disable WordPress.DB.PreparedSQL -- Hardcoded query parts without user input.
		$results = $wpdb->get_results(
			'SELECT `option_name`,`option_value`,`autoload`' . $size_query
			. " FROM `$wpdb->options` {$where}"
		);
		// phpcs:enable

		$orderby = Utils\get_flag_value( $assoc_args, 'orderby' );
		$order   = Utils\get_flag_value( $assoc_args, 'order' );

		// Sort result.
		if ( 'option_id' !== $orderby ) {
			usort(
				$results,
				function ( $a, $b ) use ( $orderby, $order ) {
					// Sort array.
					return 'asc' === $order
						? $a->$orderby > $b->$orderby
						: $a->$orderby < $b->$orderby;
				}
			);
		} elseif ( 'option_id' === $orderby && 'desc' === $order ) { // Sort by default descending.
			krsort( $results );
		}

		if ( true === Utils\get_flag_value( $assoc_args, 'unserialize', null ) ) {
			foreach ( $results as $k => &$v ) {
				if ( ! empty( $v->option_value ) ) {
					$v->option_value = maybe_unserialize( $v->option_value );
				}
			}
		}

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
	 * Updates an option value.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The name of the option to update.
	 *
	 * [<value>]
	 * : The new value. If ommited, the value is read from STDIN.
	 *
	 * [--autoload=<autoload>]
	 * : Requires WP 4.2. Should this option be automatically loaded.
	 * ---
	 * options:
	 *   - 'yes'
	 *   - 'no'
	 * ---
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
	 *     # Update an option by reading from a file.
	 *     $ wp option update my_option < value.txt
	 *     Success: Updated 'my_option' option.
	 *
	 *     # Update one option on multiple sites using xargs.
	 *     $ wp site list --field=url | xargs -n1 -I {} sh -c 'wp --url={} option update my_option my_value'
	 *     Success: Updated 'my_option' option.
	 *     Success: Updated 'my_option' option.
	 *
	 *     # Update site blog name.
	 *     $ wp option update blogname "Random blog name"
	 *     Success: Updated 'blogname' option.
	 *
	 *     # Update site blog description.
	 *     $ wp option update blogdescription "Some random blog description"
	 *     Success: Updated 'blogdescription' option.
	 *
	 *     # Update admin email address.
	 *     $ wp option update admin_email someone@example.com
	 *     Success: Updated 'admin_email' option.
	 *
	 *     # Set the default role.
	 *     $ wp option update default_role author
	 *     Success: Updated 'default_role' option.
	 *
	 *     # Set the timezone string.
	 *     $ wp option update timezone_string "America/New_York"
	 *     Success: Updated 'timezone_string' option.
	 *
	 * @alias set
	 */
	public function update( $args, $assoc_args ) {
		$key = $args[0];

		$value = WP_CLI::get_value_from_arg_or_stdin( $args, 1 );
		$value = WP_CLI::read_value( $value, $assoc_args );

		$autoload = Utils\get_flag_value( $assoc_args, 'autoload' );
		if ( ! in_array( $autoload, [ 'yes', 'no' ], true ) ) {
			$autoload = null;
		}

		$value = sanitize_option( $key, $value );
		// Sanitization WordPress normally performs when getting an option
		if ( in_array( $key, [ 'siteurl', 'home', 'category_base', 'tag_base' ], true ) ) {
			$value = untrailingslashit( $value );
		}
		$old_value = sanitize_option( $key, get_option( $key ) );

		if ( $value === $old_value && null === $autoload ) {
			WP_CLI::success( "Value passed for '{$key}' option is unchanged." );
		} else {
			if ( update_option( $key, $value, $autoload ) ) {
				WP_CLI::success( "Updated '{$key}' option." );
			} else {
				WP_CLI::error( "Could not update option '{$key}'." );
			}
		}
	}

	/**
	 * Deletes an option.
	 *
	 * ## OPTIONS
	 *
	 * <key>...
	 * : Key for the option.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete an option.
	 *     $ wp option delete my_option
	 *     Success: Deleted 'my_option' option.
	 *
	 *     # Delete multiple options.
	 *     $ wp option delete option_one option_two option_three
	 *     Success: Deleted 'option_one' option.
	 *     Success: Deleted 'option_two' option.
	 *     Warning: Could not delete 'option_three' option. Does it exist?
	 */
	public function delete( $args ) {
		foreach ( $args as $arg ) {
			if ( ! delete_option( $arg ) ) {
				WP_CLI::warning( "Could not delete '{$arg}' option. Does it exist?" );
			} else {
				WP_CLI::success( "Deleted '{$arg}' option." );
			}
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
	 * ---
	 */
	public function pluck( $args, $assoc_args ) {
		list( $key ) = $args;

		$value = get_option( $key );

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
		$old_value     = sanitize_option( $key, get_option( $key ) );
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
			WP_CLI::success( "Value passed for '{$key}' option is unchanged." );
		} else {
			if ( update_option( $key, $patched_value ) ) {
				WP_CLI::success( "Updated '{$key}' option." );
			} else {
				WP_CLI::error( "Could not update option '{$key}'." );
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
