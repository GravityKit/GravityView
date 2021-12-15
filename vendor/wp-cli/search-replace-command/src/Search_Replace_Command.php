<?php

use cli\Colors;
use cli\Table;
use WP_CLI\Iterators;
use WP_CLI\SearchReplacer;
use WP_CLI\Utils;
use function cli\safe_substr;

class Search_Replace_Command extends WP_CLI_Command {

	private $dry_run;
	private $export_handle = false;
	private $export_insert_size;
	private $recurse_objects;
	private $regex;
	private $regex_flags;
	private $regex_delimiter;
	private $regex_limit = -1;
	private $skip_tables;
	private $skip_columns;
	private $include_columns;
	private $format;
	private $report;
	private $verbose;

	private $report_changed_only;
	private $log_handle         = null;
	private $log_before_context = 40;
	private $log_after_context  = 40;
	private $log_prefixes       = array( '< ', '> ' );
	private $log_colors;
	private $log_encoding;

	/**
	 * Searches/replaces strings in the database.
	 *
	 * Searches through all rows in a selection of tables and replaces
	 * appearances of the first string with the second string.
	 *
	 * By default, the command uses tables registered to the `$wpdb` object. On
	 * multisite, this will just be the tables for the current site unless
	 * `--network` is specified.
	 *
	 * Search/replace intelligently handles PHP serialized data, and does not
	 * change primary key values.
	 *
	 * ## OPTIONS
	 *
	 * <old>
	 * : A string to search for within the database.
	 *
	 * <new>
	 * : Replace instances of the first string with this new string.
	 *
	 * [<table>...]
	 * : List of database tables to restrict the replacement to. Wildcards are
	 * supported, e.g. `'wp_*options'` or `'wp_post*'`.
	 *
	 * [--dry-run]
	 * : Run the entire search/replace operation and show report, but don't save
	 * changes to the database.
	 *
	 * [--network]
	 * : Search/replace through all the tables registered to $wpdb in a
	 * multisite install.
	 *
	 * [--all-tables-with-prefix]
	 * : Enable replacement on any tables that match the table prefix even if
	 * not registered on $wpdb.
	 *
	 * [--all-tables]
	 * : Enable replacement on ALL tables in the database, regardless of the
	 * prefix, and even if not registered on $wpdb. Overrides --network
	 * and --all-tables-with-prefix.
	 *
	 * [--export[=<file>]]
	 * : Write transformed data as SQL file instead of saving replacements to
	 * the database. If <file> is not supplied, will output to STDOUT.
	 *
	 * [--export_insert_size=<rows>]
	 * : Define number of rows in single INSERT statement when doing SQL export.
	 * You might want to change this depending on your database configuration
	 * (e.g. if you need to do fewer queries). Default: 50
	 *
	 * [--skip-tables=<tables>]
	 * : Do not perform the replacement on specific tables. Use commas to
	 * specify multiple tables. Wildcards are supported, e.g. `'wp_*options'` or `'wp_post*'`.
	 *
	 * [--skip-columns=<columns>]
	 * : Do not perform the replacement on specific columns. Use commas to
	 * specify multiple columns.
	 *
	 * [--include-columns=<columns>]
	 * : Perform the replacement on specific columns. Use commas to
	 * specify multiple columns.
	 *
	 * [--precise]
	 * : Force the use of PHP (instead of SQL) which is more thorough,
	 * but slower.
	 *
	 * [--recurse-objects]
	 * : Enable recursing into objects to replace strings. Defaults to true;
	 * pass --no-recurse-objects to disable.
	 *
	 * [--verbose]
	 * : Prints rows to the console as they're updated.
	 *
	 * [--regex]
	 * : Runs the search using a regular expression (without delimiters).
	 * Warning: search-replace will take about 15-20x longer when using --regex.
	 *
	 * [--regex-flags=<regex-flags>]
	 * : Pass PCRE modifiers to regex search-replace (e.g. 'i' for case-insensitivity).
	 *
	 * [--regex-delimiter=<regex-delimiter>]
	 * : The delimiter to use for the regex. It must be escaped if it appears in the search string. The default value is the result of `chr(1)`.
	 *
	 * [--regex-limit=<regex-limit>]
	 * : The maximum possible replacements for the regex per row (or per unserialized data bit per row). Defaults to -1 (no limit).
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - count
	 * ---
	 *
	 * [--report]
	 * : Produce report. Defaults to true.
	 *
	 * [--report-changed-only]
	 * : Report changed fields only. Defaults to false, unless logging, when it defaults to true.
	 *
	 * [--log[=<file>]]
	 * : Log the items changed. If <file> is not supplied or is "-", will output to STDOUT.
	 * Warning: causes a significant slow down, similar or worse to enabling --precise or --regex.
	 *
	 * [--before_context=<num>]
	 * : For logging, number of characters to display before the old match and the new replacement. Default 40. Ignored if not logging.
	 *
	 * [--after_context=<num>]
	 * : For logging, number of characters to display after the old match and the new replacement. Default 40. Ignored if not logging.
	 *
	 * ## EXAMPLES
	 *
	 *     # Search and replace but skip one column
	 *     $ wp search-replace 'http://example.test' 'http://example.com' --skip-columns=guid
	 *
	 *     # Run search/replace operation but dont save in database
	 *     $ wp search-replace 'foo' 'bar' wp_posts wp_postmeta wp_terms --dry-run
	 *
	 *     # Run case-insensitive regex search/replace operation (slow)
	 *     $ wp search-replace '\[foo id="([0-9]+)"' '[bar id="\1"' --regex --regex-flags='i'
	 *
	 *     # Turn your production multisite database into a local dev database
	 *     $ wp search-replace --url=example.com example.com example.test 'wp_*options' wp_blogs
	 *
	 *     # Search/replace to a SQL file without transforming the database
	 *     $ wp search-replace foo bar --export=database.sql
	 *
	 *     # Bash script: Search/replace production to development url (multisite compatible)
	 *     #!/bin/bash
	 *     if $(wp --url=http://example.com core is-installed --network); then
	 *         wp search-replace --url=http://example.com 'http://example.com' 'http://example.test' --recurse-objects --network --skip-columns=guid --skip-tables=wp_users
	 *     else
	 *         wp search-replace 'http://example.com' 'http://example.test' --recurse-objects --skip-columns=guid --skip-tables=wp_users
	 *     fi
	 */
	public function __invoke( $args, $assoc_args ) {
		global $wpdb;
		$old                   = array_shift( $args );
		$new                   = array_shift( $args );
		$total                 = 0;
		$report                = array();
		$this->dry_run         = Utils\get_flag_value( $assoc_args, 'dry-run' );
		$php_only              = Utils\get_flag_value( $assoc_args, 'precise' );
		$this->recurse_objects = Utils\get_flag_value( $assoc_args, 'recurse-objects', true );
		$this->verbose         = Utils\get_flag_value( $assoc_args, 'verbose' );
		$this->format          = Utils\get_flag_value( $assoc_args, 'format' );
		$this->regex           = Utils\get_flag_value( $assoc_args, 'regex', false );

		if ( null !== $this->regex ) {
			$default_regex_delimiter = false;
			$this->regex_flags       = Utils\get_flag_value( $assoc_args, 'regex-flags', false );
			$this->regex_delimiter   = Utils\get_flag_value( $assoc_args, 'regex-delimiter', '' );
			if ( '' === $this->regex_delimiter ) {
				$this->regex_delimiter   = chr( 1 );
				$default_regex_delimiter = true;
			}
		}

		$regex_limit = Utils\get_flag_value( $assoc_args, 'regex-limit' );
		if ( null !== $regex_limit ) {
			if ( ! preg_match( '/^(?:[0-9]+|-1)$/', $regex_limit ) || 0 === (int) $regex_limit ) {
				WP_CLI::error( '`--regex-limit` expects a non-zero positive integer or -1.' );
			}
			$this->regex_limit = (int) $regex_limit;
		}

		if ( ! empty( $this->regex ) ) {
			if ( '' === $this->regex_delimiter ) {
				$this->regex_delimiter = chr( 1 );
			}
			$search_regex  = $this->regex_delimiter;
			$search_regex .= $old;
			$search_regex .= $this->regex_delimiter;
			$search_regex .= $this->regex_flags;

			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Preventing a warning when testing the regex.
			if ( false === @preg_match( $search_regex, '' ) ) {
				$error              = error_get_last();
				$preg_error_message = ( ! empty( $error ) && array_key_exists( 'message', $error ) ) ? "\n{$error['message']}." : '';
				if ( $default_regex_delimiter ) {
					$flags_msg = $this->regex_flags ? "flags '$this->regex_flags'" : 'no flags';
					$msg       = "The regex pattern '$old' with default delimiter 'chr(1)' and {$flags_msg} fails.";
				} else {
					$msg = "The regex '$search_regex' fails.";
				}
				WP_CLI::error( $msg . $preg_error_message );
			}
		}

		$this->skip_columns    = explode( ',', Utils\get_flag_value( $assoc_args, 'skip-columns' ) );
		$this->skip_tables     = explode( ',', Utils\get_flag_value( $assoc_args, 'skip-tables' ) );
		$this->include_columns = array_filter( explode( ',', Utils\get_flag_value( $assoc_args, 'include-columns' ) ) );

		if ( $old === $new && ! $this->regex ) {
			WP_CLI::warning( "Replacement value '{$old}' is identical to search value '{$new}'. Skipping operation." );
			exit;
		}

		$export = Utils\get_flag_value( $assoc_args, 'export' );
		if ( null !== $export ) {
			if ( $this->dry_run ) {
				WP_CLI::error( 'You cannot supply --dry-run and --export at the same time.' );
			}
			if ( true === $export ) {
				$this->export_handle = STDOUT;
				$this->verbose       = false;
			} else {
				$this->export_handle = @fopen( $assoc_args['export'], 'w' );
				if ( false === $this->export_handle ) {
					$error = error_get_last();
					WP_CLI::error( sprintf( 'Unable to open export file "%s" for writing: %s.', $assoc_args['export'], $error['message'] ) );
				}
			}
			$export_insert_size = Utils\get_flag_value( $assoc_args, 'export_insert_size', 50 );
			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- See the code, this is deliberate.
			if ( (int) $export_insert_size == $export_insert_size && $export_insert_size > 0 ) {
				$this->export_insert_size = $export_insert_size;
			}
			$php_only = true;
		}

		$log = Utils\get_flag_value( $assoc_args, 'log' );
		if ( null !== $log ) {
			if ( true === $log || '-' === $log ) {
				$this->log_handle = STDOUT;
			} else {
				$this->log_handle = @fopen( $assoc_args['log'], 'w' );
				if ( false === $this->log_handle ) {
					$error = error_get_last();
					WP_CLI::error( sprintf( 'Unable to open log file "%s" for writing: %s.', $assoc_args['log'], $error['message'] ) );
				}
			}
			if ( $this->log_handle ) {
				$before_context = Utils\get_flag_value( $assoc_args, 'before_context' );
				if ( null !== $before_context && preg_match( '/^[0-9]+$/', $before_context ) ) {
					$this->log_before_context = (int) $before_context;
				}

				$after_context = Utils\get_flag_value( $assoc_args, 'after_context' );
				if ( null !== $after_context && preg_match( '/^[0-9]+$/', $after_context ) ) {
					$this->log_after_context = (int) $after_context;
				}

				$log_prefixes = getenv( 'WP_CLI_SEARCH_REPLACE_LOG_PREFIXES' );
				if ( false !== $log_prefixes && preg_match( '/^([^,]*),([^,]*)$/', $log_prefixes, $matches ) ) {
					$this->log_prefixes = array( $matches[1], $matches[2] );
				}

				if ( STDOUT === $this->log_handle ) {
					$default_log_colors = array(
						'log_table_column_id' => '%B',
						'log_old'             => '%R',
						'log_new'             => '%G',
					);
				} else {
					$default_log_colors = array(
						'log_table_column_id' => '',
						'log_old'             => '',
						'log_new'             => '',
					);
				}

				$log_colors = getenv( 'WP_CLI_SEARCH_REPLACE_LOG_COLORS' );
				if ( false !== $log_colors && preg_match( '/^([^,]*),([^,]*),([^,]*)$/', $log_colors, $matches ) ) {
					$default_log_colors = array(
						'log_table_column_id' => $matches[1],
						'log_old'             => $matches[2],
						'log_new'             => $matches[3],
					);
				}

				$this->log_colors   = $this->get_colors( $assoc_args, $default_log_colors );
				$this->log_encoding = 0 === strpos( $wpdb->charset, 'utf8' ) ? 'UTF-8' : false;
			}
		}

		$this->report = Utils\get_flag_value( $assoc_args, 'report', true );
		// Defaults to true if logging, else defaults to false.
		$this->report_changed_only = Utils\get_flag_value( $assoc_args, 'report-changed-only', null !== $this->log_handle );

		if ( $this->regex_flags ) {
			$php_only = true;
		}

		// never mess with hashed passwords
		$this->skip_columns[] = 'user_pass';

		// Get table names based on leftover $args or supplied $assoc_args
		$tables = Utils\wp_get_table_names( $args, $assoc_args );

		foreach ( $tables as $table ) {

			foreach ( $this->skip_tables as $skip_table ) {
				if ( fnmatch( $skip_table, $table ) ) {
					continue 2;
				}
			}

			$table_sql = self::esc_sql_ident( $table );

			if ( $this->export_handle ) {
				fwrite( $this->export_handle, "\nDROP TABLE IF EXISTS $table_sql;\n" );

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- escaped through self::esc_sql_ident
				$row = $wpdb->get_row( "SHOW CREATE TABLE $table_sql", ARRAY_N );

				fwrite( $this->export_handle, $row[1] . ";\n" );
				list( $table_report, $total_rows ) = $this->php_export_table( $table, $old, $new );
				if ( $this->report ) {
					$report = array_merge( $report, $table_report );
				}
				$total += $total_rows;
				// Don't perform replacements on the actual database
				continue;
			}

			list( $primary_keys, $columns, $all_columns ) = self::get_columns( $table );

			// since we'll be updating one row at a time,
			// we need a primary key to identify the row
			if ( empty( $primary_keys ) ) {

				// wasn't updated, so skip to the next table
				if ( $this->report_changed_only ) {
					continue;
				}
				if ( $this->report ) {
					$report[] = array( $table, '', 'skipped', '' );
				} else {
					WP_CLI::warning( $all_columns ? "No primary keys for table '$table'." : "No such table '$table'." );
				}
				continue;
			}

			foreach ( $columns as $col ) {
				if ( ! empty( $this->include_columns ) && ! in_array( $col, $this->include_columns, true ) ) {
					continue;
				}

				if ( in_array( $col, $this->skip_columns, true ) ) {
					continue;
				}

				if ( $this->verbose && 'count' !== $this->format ) {
					$this->start_time = microtime( true );
					WP_CLI::log( sprintf( 'Checking: %s.%s', $table, $col ) );
				}

				if ( ! $php_only && ! $this->regex ) {
					$col_sql          = self::esc_sql_ident( $col );
					$wpdb->last_error = '';

					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- escaped through self::esc_sql_ident
					$serial_row = $wpdb->get_row( "SELECT * FROM $table_sql WHERE $col_sql REGEXP '^[aiO]:[1-9]' LIMIT 1" );

					// When the regex triggers an error, we should fall back to PHP
					if ( false !== strpos( $wpdb->last_error, 'ERROR 1139' ) ) {
						$serial_row = true;
					}
				}

				if ( $php_only || $this->regex || null !== $serial_row ) {
					$type  = 'PHP';
					$count = $this->php_handle_col( $col, $primary_keys, $table, $old, $new );
				} else {
					$type  = 'SQL';
					$count = $this->sql_handle_col( $col, $primary_keys, $table, $old, $new );
				}

				if ( $this->report && ( $count || ! $this->report_changed_only ) ) {
					$report[] = array( $table, $col, $count, $type );
				}

				$total += $count;
			}
		}

		if ( $this->export_handle && STDOUT !== $this->export_handle ) {
			fclose( $this->export_handle );
		}

		// Only informational output after this point
		if ( WP_CLI::get_config( 'quiet' ) || STDOUT === $this->export_handle ) {
			return;
		}

		if ( 'count' === $this->format ) {
			WP_CLI::line( $total );
			return;
		}

		if ( $this->report && ! empty( $report ) ) {
			$table = new Table();
			$table->setHeaders( array( 'Table', 'Column', 'Replacements', 'Type' ) );
			$table->setRows( $report );
			$table->display();
		}

		if ( ! $this->dry_run ) {
			if ( ! empty( $assoc_args['export'] ) ) {
				$success_message = 1 === $total ? "Made 1 replacement and exported to {$assoc_args['export']}." : "Made {$total} replacements and exported to {$assoc_args['export']}.";
			} else {
				$success_message = 1 === $total ? 'Made 1 replacement.' : "Made $total replacements.";
				if ( $total && 'Default' !== Utils\wp_get_cache_type() ) {
					$success_message .= ' Please remember to flush your persistent object cache with `wp cache flush`.';
				}
			}
			WP_CLI::success( $success_message );
		} else {
			$success_message = ( 1 === $total ) ? '%d replacement to be made.' : '%d replacements to be made.';
			WP_CLI::success( sprintf( $success_message, $total ) );
		}
	}

	private function php_export_table( $table, $old, $new ) {
		list( $primary_keys, $columns, $all_columns ) = self::get_columns( $table );

		$chunk_size = getenv( 'BEHAT_RUN' ) ? 10 : 1000;
		$args       = array(
			'table'      => $table,
			'fields'     => $all_columns,
			'chunk_size' => $chunk_size,
		);

		$replacer   = new SearchReplacer( $old, $new, $this->recurse_objects, $this->regex, $this->regex_flags, $this->regex_delimiter, false, $this->regex_limit );
		$col_counts = array_fill_keys( $all_columns, 0 );
		if ( $this->verbose && 'table' === $this->format ) {
			$this->start_time = microtime( true );
			WP_CLI::log( sprintf( 'Checking: %s', $table ) );
		}

		$rows = array();
		foreach ( new Iterators\Table( $args ) as $i => $row ) {
			$row_fields = array();
			foreach ( $all_columns as $col ) {
				$value = $row->$col;
				if ( $value && ! in_array( $col, $primary_keys, true ) && ! in_array( $col, $this->skip_columns, true ) ) {
					$new_value = $replacer->run( $value );
					if ( $new_value !== $value ) {
						$col_counts[ $col ]++;
						$value = $new_value;
					}
				}
				$row_fields[ $col ] = $value;
			}
			$rows[] = $row_fields;
		}
		$this->write_sql_row_fields( $table, $rows );

		$table_report = array();
		$total_rows   = 0;
		$total_cols   = 0;
		foreach ( $col_counts as $col => $col_count ) {
			if ( $this->report && ( $col_count || ! $this->report_changed_only ) ) {
				$table_report[] = array( $table, $col, $col_count, 'PHP' );
			}
			if ( $col_count ) {
				$total_cols++;
				$total_rows += $col_count;
			}
		}

		if ( $this->verbose && 'table' === $this->format ) {
			$time = round( microtime( true ) - $this->start_time, 3 );
			WP_CLI::log( sprintf( '%d columns and %d total rows affected using PHP (in %ss).', $total_cols, $total_rows, $time ) );
		}

		return array( $table_report, $total_rows );
	}

	private function sql_handle_col( $col, $primary_keys, $table, $old, $new ) {
		global $wpdb;

		$table_sql = self::esc_sql_ident( $table );
		$col_sql   = self::esc_sql_ident( $col );
		if ( $this->dry_run ) {
			if ( $this->log_handle ) {
				$count = $this->log_sql_diff( $col, $primary_keys, $table, $old, $new );
			} else {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- escaped through self::esc_sql_ident
				$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT($col_sql) FROM $table_sql WHERE $col_sql LIKE BINARY %s;", '%' . self::esc_like( $old ) . '%' ) );
			}
		} else {
			if ( $this->log_handle ) {
				$this->log_sql_diff( $col, $primary_keys, $table, $old, $new );
			}
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- escaped through self::esc_sql_ident
			$count = $wpdb->query( $wpdb->prepare( "UPDATE $table_sql SET $col_sql = REPLACE($col_sql, %s, %s);", $old, $new ) );
		}

		if ( $this->verbose && 'table' === $this->format ) {
			$time = round( microtime( true ) - $this->start_time, 3 );
			WP_CLI::log( sprintf( '%d rows affected using SQL (in %ss).', $count, $time ) );
		}
		return $count;
	}

	private function php_handle_col( $col, $primary_keys, $table, $old, $new ) {
		global $wpdb;

		$count    = 0;
		$replacer = new SearchReplacer( $old, $new, $this->recurse_objects, $this->regex, $this->regex_flags, $this->regex_delimiter, null !== $this->log_handle, $this->regex_limit );

		$table_sql            = self::esc_sql_ident( $table );
		$col_sql              = self::esc_sql_ident( $col );
		$where                = $this->regex ? '' : " WHERE $col_sql" . $wpdb->prepare( ' LIKE BINARY %s', '%' . self::esc_like( $old ) . '%' );
		$escaped_primary_keys = self::esc_sql_ident( $primary_keys );
		$primary_keys_sql     = implode( ',', $escaped_primary_keys );
		$order_by_keys        = array_map(
			static function ( $key ) {
				return "{$key} ASC";
			},
			$escaped_primary_keys
		);
		$order_by_sql         = 'ORDER BY ' . implode( ',', $order_by_keys );
		$limit                = 1000;
		$offset               = 0;

		// Updates have to be deferred to after the chunking is completed, as
		// the offset will otherwise not work correctly.
		$updates = [];

		// 2 errors:
		// - WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- escaped through self::esc_sql_ident
		// - WordPress.CodeAnalysis.AssignmentInCondition -- no reason to do copy-paste for a single valid assignment in while
		// phpcs:ignore
		while ( $rows = $wpdb->get_results( "SELECT {$primary_keys_sql} FROM {$table_sql} {$where} {$order_by_sql} LIMIT {$limit} OFFSET {$offset}" ) ) {
			foreach ( $rows as $keys ) {
				$where_sql = '';
				foreach ( (array) $keys as $k => $v ) {
					if ( '' !== $where_sql ) {
						$where_sql .= ' AND ';
					}
					$where_sql .= self::esc_sql_ident( $k ) . ' = ' . self::esc_sql_value( $v );
				}

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- escaped through self::esc_sql_ident
				$col_value = $wpdb->get_var( "SELECT {$col_sql} FROM {$table_sql} WHERE {$where_sql}" );

				if ( '' === $col_value ) {
					continue;
				}

				$value = $replacer->run( $col_value );

				if ( $value === $col_value ) {
					continue;
				}

				if ( $this->log_handle ) {
					$this->log_php_diff( $col, $keys, $table, $old, $new, $replacer->get_log_data() );
					$replacer->clear_log_data();
				}

				$count++;
				if ( ! $this->dry_run ) {
					$update_where = array();
					foreach ( (array) $keys as $k => $v ) {
						$update_where[ $k ] = $v;
					}

					$updates[] = [ $table, array( $col => $value ), $update_where ];
				}
			}

			$offset += $limit;
		}

		foreach ( $updates as $update ) {
			$wpdb->update( ...$update );
		}

		if ( $this->verbose && 'table' === $this->format ) {
			$time = round( microtime( true ) - $this->start_time, 3 );
			WP_CLI::log( sprintf( '%d rows affected using PHP (in %ss).', $count, $time ) );
		}

		return $count;
	}

	private function write_sql_row_fields( $table, $rows ) {
		global $wpdb;

		if ( empty( $rows ) ) {
			return;
		}

		$table_sql = self::esc_sql_ident( $table );

		$insert  = "INSERT INTO $table_sql (";
		$insert .= join( ', ', self::esc_sql_ident( array_keys( $rows[0] ) ) );
		$insert .= ') VALUES ';
		$insert .= "\n";

		$sql    = $insert;
		$values = array();

		$index              = 1;
		$count              = count( $rows );
		$export_insert_size = $this->export_insert_size;

		foreach ( $rows as $row_fields ) {
			$subs = array();

			foreach ( $row_fields as $field_value ) {
				if ( null === $field_value ) {
					$subs[] = 'NULL';
				} else {
					$subs[]   = '%s';
					$values[] = $field_value;
				}
			}

			$sql .= '(' . join( ', ', $subs ) . ')';

			// Add new insert statement if needed. Before this we close the previous with semicolon and write statement to sql-file.
			// "Statement break" is needed:
			//		1. When the loop is running every nth time (where n is insert statement size, $export_index_size). Remainder is zero also on first round, so it have to be excluded.
			//			$index % $export_insert_size == 0 && $index > 0
			//		2. Or when the loop is running last time
			//			$index == $count
			if ( ( 0 === $index % $export_insert_size && $index > 0 ) || $index === $count ) {
				$sql .= ";\n";

				if ( method_exists( $wpdb, 'remove_placeholder_escape' ) ) {
					// since 4.8.3
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- verified inputs above
					$sql = $wpdb->remove_placeholder_escape( $wpdb->prepare( $sql, array_values( $values ) ) );
				} else {
					// 4.8.2 or less
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- verified inputs above
					$sql = $wpdb->prepare( $sql, array_values( $values ) );
				}

				fwrite( $this->export_handle, $sql );

				// If there is still rows to loop, reset $sql and $values variables.
				if ( $count > $index ) {
					$sql    = $insert;
					$values = array();
				}
			} else { // Otherwise just add comma and new line
				$sql .= ",\n";
			}

			$index++;
		}
	}

	private static function get_columns( $table ) {
		global $wpdb;

		$table_sql       = self::esc_sql_ident( $table );
		$primary_keys    = array();
		$text_columns    = array();
		$all_columns     = array();
		$suppress_errors = $wpdb->suppress_errors();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- escaped through self::esc_sql_ident
		$results = $wpdb->get_results( "DESCRIBE $table_sql" );

		if ( ! empty( $results ) ) {
			foreach ( $results as $col ) {
				if ( 'PRI' === $col->Key ) {
					$primary_keys[] = $col->Field;
				}
				if ( self::is_text_col( $col->Type ) ) {
					$text_columns[] = $col->Field;
				}
				$all_columns[] = $col->Field;
			}
		}
		$wpdb->suppress_errors( $suppress_errors );
		return array( $primary_keys, $text_columns, $all_columns );
	}

	private static function is_text_col( $type ) {
		foreach ( array( 'text', 'varchar' ) as $token ) {
			if ( false !== strpos( $type, $token ) ) {
				return true;
			}
		}

		return false;
	}

	private static function esc_like( $old ) {
		global $wpdb;

		// Remove notices in 4.0 and support backwards compatibility
		if ( method_exists( $wpdb, 'esc_like' ) ) {
			// 4.0
			$old = $wpdb->esc_like( $old );
		} else {
			// phpcs:ignore WordPress.WP.DeprecatedFunctions.like_escapeFound -- BC-layer for WP 3.9 or less.
			$old = like_escape( esc_sql( $old ) ); // Note: this double escaping is actually necessary, even though `esc_like()` will be used in a `prepare()`.
		}

		return $old;
	}

	/**
	 * Escapes (backticks) MySQL identifiers (aka schema object names) - i.e. column names, table names, and database/index/alias/view etc names.
	 * See https://dev.mysql.com/doc/refman/5.5/en/identifiers.html
	 *
	 * @param string|array $idents A single identifier or an array of identifiers.
	 * @return string|array An escaped string if given a string, or an array of escaped strings if given an array of strings.
	 */
	private static function esc_sql_ident( $idents ) {
		$backtick = static function ( $v ) {
			// Escape any backticks in the identifier by doubling.
			return '`' . str_replace( '`', '``', $v ) . '`';
		};
		if ( is_string( $idents ) ) {
			return $backtick( $idents );
		}
		return array_map( $backtick, $idents );
	}

	/**
	 * Puts MySQL string values in single quotes, to avoid them being interpreted as column names.
	 *
	 * @param string|array $values A single value or an array of values.
	 * @return string|array A quoted string if given a string, or an array of quoted strings if given an array of strings.
	 */
	private static function esc_sql_value( $values ) {
		$quote = static function ( $v ) {
			// Don't quote integer values to avoid MySQL's implicit type conversion.
			if ( preg_match( '/^[+-]?[0-9]{1,20}$/', $v ) ) { // MySQL BIGINT UNSIGNED max 18446744073709551615 (20 digits).
				return esc_sql( $v );
			}

			// Put any string values between single quotes.
			return "'" . esc_sql( $v ) . "'";
		};

		if ( is_array( $values ) ) {
			return array_map( $quote, $values );
		}

		return $quote( $values );
	}

	/**
	 * Gets the color codes from the options if any, and returns the passed in array colorized with 2 elements per entry, a color code (or '') and a reset (or '').
	 *
	 * @param array $assoc_args The associative argument array passed to the command.
	 * @param array $colors Array of default percent color code strings keyed by the color contexts.
	 * @return array Array containing 2-element arrays keyed to the input $colors array.
	 */
	private function get_colors( $assoc_args, $colors ) {
		$color_reset = WP_CLI::colorize( '%n' );

		$color_code_callback = static function ( $v ) {
			return substr( $v, 1 );
		};

		$color_codes = array_keys( Colors::getColors() );
		$color_codes = array_map( $color_code_callback, $color_codes );
		$color_codes = implode( '', $color_codes );

		$color_codes_regex = '/^(?:%[' . $color_codes . '])*$/';

		foreach ( array_keys( $colors ) as $color_col ) {
			$col_color_flag = Utils\get_flag_value( $assoc_args, $color_col . '_color' );
			if ( null !== $col_color_flag ) {
				if ( ! preg_match( $color_codes_regex, $col_color_flag, $matches ) ) {
					WP_CLI::warning( "Unrecognized percent color code '$col_color_flag' for '{$color_col}_color'." );
				} else {
					$colors[ $color_col ] = $matches[0];
				}
			}
			$colors[ $color_col ] = $colors[ $color_col ] ? array( WP_CLI::colorize( $colors[ $color_col ] ), $color_reset ) : array( '', '' );
		}

		return $colors;
	}

	/*
	 * Logs the difference between old match and new replacement for SQL replacement.
	 *
	 * @param string $col Column being processed.
	 * @param array $primary_keys Primary keys for table.
	 * @param string $table Table being processed.
	 * @param string $old Old value to match.
	 * @param string $new New value to replace the old value with.
	 * @return int Count of changed rows.
	 */
	private function log_sql_diff( $col, $primary_keys, $table, $old, $new ) {
		global $wpdb;
		if ( $primary_keys ) {
			$esc_primary_keys = implode( ', ', self::esc_sql_ident( $primary_keys ) );
			$primary_keys_sql = count( $primary_keys ) > 1 ? "CONCAT_WS(',', {$esc_primary_keys}), " : "{$esc_primary_keys}, ";
		} else {
			$primary_keys_sql = '';
		}

		$table_sql = self::esc_sql_ident( $table );
		$col_sql   = self::esc_sql_ident( $col );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- escaped through self::esc_sql_ident
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT {$primary_keys_sql}{$col_sql} FROM {$table_sql} WHERE {$col_sql} LIKE BINARY %s", '%' . self::esc_like( $old ) . '%' ), ARRAY_N );

		if ( empty( $results ) ) {
			return 0;
		}

		$search_regex = '/' . preg_quote( $old, '/' ) . '/';

		foreach ( $results as $result ) {
			list( $keys, $data ) = $primary_keys ? array( $result[0], $result[1] ) : array( null, $result[0] );
			if ( preg_match_all( $search_regex, $data, $matches, PREG_OFFSET_CAPTURE ) ) {
				list( $old_bits, $new_bits ) = $this->log_bits( $search_regex, $data, $matches, $new );
				$this->log_write( $col, $keys, $table, $old_bits, $new_bits );
			}
		}
		return count( $results );
	}

	/*
	 * Logs the difference between old matches and new replacements at the end of a PHP (regex) replacement of a database row.
	 *
	 * @param string $col Column being processed.
	 * @param array $keys Associative array (or object) of primary key names and their values for the row being processed.
	 * @param string $table Table being processed.
	 * @param string $old Old value to match.
	 * @param string $new New value to replace the old value with.
	 * @param array $log_data Array of data strings before replacements.
	 */
	private function log_php_diff( $col, $keys, $table, $old, $new, $log_data ) {
		if ( $this->regex ) {
			$search_regex = $this->regex_delimiter . $old . $this->regex_delimiter . $this->regex_flags;
		} else {
			$search_regex = '/' . preg_quote( $old, '/' ) . '/';
		}

		$old_bits = array();
		$new_bits = array();
		foreach ( $log_data as $data ) {
			if ( preg_match_all( $search_regex, $data, $matches, PREG_OFFSET_CAPTURE ) ) {
				$bits     = $this->log_bits( $search_regex, $data, $matches, $new );
				$old_bits = array_merge( $old_bits, $bits[0] );
				$new_bits = array_merge( $new_bits, $bits[1] );
			}
		}
		if ( $old_bits ) {
			$this->log_write( $col, $keys, $table, $old_bits, $new_bits );
		}
	}

	/**
	 * Returns the arrays of old matches and new replacements based on the passed-in matches, with context.
	 *
	 * @param string $search_regex The search regular expression.
	 * @param string $old_data Existing data being processed.
	 * @param array $old_matches Old matches array returned by `preg_match_all()`.
	 * @param string $new New value to replace the old value with.
	 * @return array Two element array containing the array of old match log strings and the array of new replacement log strings with before/after contexts.
	 */
	private function log_bits( $search_regex, $old_data, $old_matches, $new ) {
		$encoding = $this->log_encoding;
		if ( ! $encoding && ( $this->log_before_context || $this->log_after_context ) && function_exists( 'mb_detect_encoding' ) ) {
			$encoding = mb_detect_encoding( $old_data, null, true /*strict*/ );
		}

		// Generate a new data matches analog of the old data matches by simulating a `preg_replace()`.
		$is_regex    = $this->regex;
		$i           = 0;
		$diff        = 0;
		$new_matches = array();
		$new_data    = preg_replace_callback(
			$search_regex,
			static function ( $matches ) use ( $old_matches, $new, $is_regex, &$new_matches, &$i, &$diff ) {
				if ( $is_regex ) {
					// Sub in any back references, "$1", "\2" etc, in the replacement string.
					$new = preg_replace_callback(
						'/(?<!\\\\)(?:\\\\\\\\)*((?:\\\\|\\$)[0-9]{1,2}|\\${[0-9]{1,2}\\})/',
						static function ( $m ) use ( $matches ) {
							$idx = (int) str_replace( array( '\\', '$', '{', '}' ), '', $m[0] );
							return isset( $matches[ $idx ] ) ? $matches[ $idx ] : '';
						},
						$new
					);
					$new = str_replace( '\\\\', '\\', $new ); // Unescape any backslashed backslashes.
				}

				$new_matches[0][ $i ][0] = $new;
				$new_matches[0][ $i ][1] = $old_matches[0][ $i ][1] + $diff;

				$diff += strlen( $new ) - strlen( $old_matches[0][ $i ][0] );
				$i++;
				return $new;
			},
			$old_data,
			$this->regex_limit,
			$match_cnt
		);

		$old_bits        = array();
		$new_bits        = array();
		$append_next     = false;
		$last_old_offset = 0;
		$last_new_offset = 0;
		for ( $i = 0; $i < $match_cnt; $i++ ) {
			$old_match  = $old_matches[0][ $i ][0];
			$old_offset = $old_matches[0][ $i ][1];
			$new_match  = $new_matches[0][ $i ][0];
			$new_offset = $new_matches[0][ $i ][1];

			$old_log = $this->log_colors['log_old'][0] . $old_match . $this->log_colors['log_old'][1];
			$new_log = $this->log_colors['log_new'][0] . $new_match . $this->log_colors['log_new'][1];

			$old_before      = '';
			$old_after       = '';
			$new_before      = '';
			$new_after       = '';
			$after_shortened = false;

			// Offsets are in bytes, so need to use `strlen()` and `substr()` before using `safe_substr()`.
			if ( $this->log_before_context && $old_offset && ! $append_next ) {
				$old_before = safe_substr( substr( $old_data, $last_old_offset, $old_offset - $last_old_offset ), -$this->log_before_context, null /*length*/, false /*is_width*/, $encoding );
				$new_before = safe_substr( substr( $new_data, $last_new_offset, $new_offset - $last_new_offset ), -$this->log_before_context, null /*length*/, false /*is_width*/, $encoding );
			}
			if ( $this->log_after_context ) {
				$old_end_offset = $old_offset + strlen( $old_match );
				$new_end_offset = $new_offset + strlen( $new_match );
				$old_after      = safe_substr( substr( $old_data, $old_end_offset ), 0, $this->log_after_context, false /*is_width*/, $encoding );
				$new_after      = safe_substr( substr( $new_data, $new_end_offset ), 0, $this->log_after_context, false /*is_width*/, $encoding );
				// To lessen context duplication in output, shorten the after context if it overlaps with the next match.
				if ( $i + 1 < $match_cnt && $old_end_offset + strlen( $old_after ) > $old_matches[0][ $i + 1 ][1] ) {
					$old_after       = substr( $old_after, 0, $old_matches[0][ $i + 1 ][1] - $old_end_offset );
					$new_after       = substr( $new_after, 0, $new_matches[0][ $i + 1 ][1] - $new_end_offset );
					$after_shortened = true;
					// On the next iteration, will append with no before context.
				}
			}

			if ( $append_next ) {
				$cnt                   = count( $old_bits );
				$old_bits[ $cnt - 1 ] .= $old_log . $old_after;
				$new_bits[ $cnt - 1 ] .= $new_log . $new_after;
			} else {
				$old_bits[] = $old_before . $old_log . $old_after;
				$new_bits[] = $new_before . $new_log . $new_after;
			}
			$append_next     = $after_shortened;
			$last_old_offset = $old_offset;
			$last_new_offset = $new_offset;
		}

		return array( $old_bits, $new_bits );
	}

	/*
	 * Outputs the log strings.
	 *
	 * @param string $col Column being processed.
	 * @param array $keys Associative array (or object) of primary key names and their values for the row being processed.
	 * @param string $table Table being processed.
	 * @param array $old_bits Array of old match log strings.
	 * @param array $new_bits Array of new replacement log strings.
	 */
	private function log_write( $col, $keys, $table, $old_bits, $new_bits ) {
		$id_log              = $keys ? ( ':' . implode( ',', (array) $keys ) ) : '';
		$table_column_id_log = $this->log_colors['log_table_column_id'][0] . $table . '.' . $col . $id_log . $this->log_colors['log_table_column_id'][1];

		$old_log = str_replace( array( "\r\n", "\n" ), ' ', implode( ' [...] ', $old_bits ) );
		$new_log = str_replace( array( "\r\n", "\n" ), ' ', implode( ' [...] ', $new_bits ) );

		if ( $this->log_prefixes[0] ) {
			$old_log = $this->log_colors['log_old'][0] . $this->log_prefixes[0] . $this->log_colors['log_old'][1] . $old_log;
		}
		if ( $this->log_prefixes[1] ) {
			$new_log = $this->log_colors['log_new'][0] . $this->log_prefixes[1] . $this->log_colors['log_new'][1] . $new_log;
		}

		fwrite( $this->log_handle, "{$table_column_id_log}\n{$old_log}\n{$new_log}\n" );
	}

}
