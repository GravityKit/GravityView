<?php

namespace WP_CLI;

use Composer\Semver\Comparator;
use Exception;
use WP_CLI;
use WP_CLI\Fetchers;
use WP_CLI\Loggers;
use WP_CLI\Utils;
use WP_Error;

abstract class CommandWithUpgrade extends \WP_CLI_Command {

	protected $item_type;
	protected $obj_fields;

	protected $upgrade_refresh;
	protected $upgrade_transient;

	protected $chained_command = false;

	// Invalid version message.
	const INVALID_VERSION_MESSAGE = 'version higher than expected';

	public function __construct() {

		// Do not automatically check translations updates after updating plugins/themes.
		add_action(
			'upgrader_process_complete',
			function() {
				remove_action( 'upgrader_process_complete', [ 'Language_Pack_Upgrader', 'async_upgrade' ], 20 );
			},
			1
		);

		add_filter(
			'http_request_timeout',
			function () {
				return 1 * MINUTE_IN_SECONDS;
			},
			999
		);

		$this->fetcher = new Fetchers\Plugin();
	}

	abstract protected function get_upgrader_class( $force );

	abstract protected function get_item_list();

	/**
	 * @param array List of update candidates
	 * @param array List of item names
	 * @return array List of update candidates
	 */
	abstract protected function filter_item_list( $items, $args );

	abstract protected function get_all_items();

	abstract protected function get_status( $file );

	abstract protected function status_single( $args );

	abstract protected function install_from_repo( $slug, $assoc_args );

	public function status( $args ) {
		// Force WordPress to check for updates.
		call_user_func( $this->upgrade_refresh );

		if ( empty( $args ) ) {
			$this->status_all();
		} else {
			$this->status_single( $args );
		}
	}

	private function status_all() {
		$items = $this->get_all_items();

		$n = count( $items );

		WP_CLI::log(
			sprintf( '%d installed %s:', $n, Utils\pluralize( $this->item_type, absint( $n ) ) )
		);

		$padding = $this->get_padding( $items );

		foreach ( $items as $file => $details ) {
			if ( $details['update'] ) {
				$line = ' %yU%n';
			} else {
				$line = '  ';
			}

			$line .= $this->format_status( $details['status'], 'short' );
			$line .= ' ' . str_pad( $details['name'], $padding ) . '%n';
			if ( ! empty( $details['version'] ) ) {
				$line .= ' ' . $details['version'];
			}

			WP_CLI::line( WP_CLI::colorize( $line ) );
		}

		WP_CLI::line();

		$this->show_legend( $items );
	}

	private function get_padding( $items ) {
		$max_len = 0;

		foreach ( $items as $details ) {
			$len = strlen( $details['name'] );

			if ( $len > $max_len ) {
				$max_len = $len;
			}
		}

		return $max_len;
	}

	private function show_legend( $items ) {
		$statuses = array_unique( wp_list_pluck( $items, 'status' ) );

		$legend_line = array();

		foreach ( $statuses as $status ) {
			$legend_line[] = sprintf(
				'%s%s = %s%%n',
				$this->get_color( $status ),
				$this->map['short'][ $status ],
				$this->map['long'][ $status ]
			);
		}

		if ( in_array( true, wp_list_pluck( $items, 'update' ), true ) ) {
			$legend_line[] = '%yU = Update Available%n';
		}

		WP_CLI::line( 'Legend: ' . WP_CLI::colorize( implode( ', ', $legend_line ) ) );
	}

	public function install( $args, $assoc_args ) {

		$successes = 0;
		$errors    = 0;
		foreach ( $args as $slug ) {

			if ( empty( $slug ) ) {
				WP_CLI::warning( 'Ignoring ambiguous empty slug value.' );
				continue;
			}

			$result = false;

			$is_remote = false !== strpos( $slug, '://' );

			// Check if a URL to a remote or local zip has been specified.
			if ( $is_remote || ( pathinfo( $slug, PATHINFO_EXTENSION ) === 'zip' && is_file( $slug ) ) ) {
				// Install from local or remote zip file.
				$file_upgrader = $this->get_upgrader( $assoc_args );

				$filter = false;
				// If a GitHub URL, do some guessing as to the correct plugin/theme directory.
				if ( $is_remote && 'github.com' === $this->parse_url_host_component( $slug, PHP_URL_HOST )
						// Don't attempt to rename ZIPs uploaded to the releases page or coming from a raw source.
						&& ! preg_match( '#github\.com/[^/]+/[^/]+/(?:releases/download|raw)/#', $slug ) ) {

					$filter = function( $source, $remote_source, $upgrader ) use ( $slug ) {

						$slug_dir = Utils\basename( $this->parse_url_host_component( $slug, PHP_URL_PATH ), '.zip' );

						// Don't use the zip name if archive attached to release, as name likely to contain version tag/branch.
						if ( preg_match( '#github\.com/[^/]+/([^/]+)/archive/#', $slug, $matches ) ) {
							// Note this will be wrong if the project name isn't the same as the plugin/theme slug name.
							$slug_dir = $matches[1];
						}

						$source_dir = Utils\basename( $source ); // `$source` is trailing-slashed path to the unzipped archive directory, so basename returns the unslashed directory.
						if ( $source_dir === $slug_dir ) {
							return $source;
						}
						$new_path = substr_replace( $source, $slug_dir, strrpos( $source, $source_dir ), strlen( $source_dir ) );

						if ( $GLOBALS['wp_filesystem']->move( $source, $new_path ) ) {
							WP_CLI::log( sprintf( "Renamed Github-based project from '%s' to '%s'.", $source_dir, $slug_dir ) );
							return $new_path;
						}

						return new WP_Error( 'wpcli_install_github', "Couldn't move Github-based project to appropriate directory." );
					};
					add_filter( 'upgrader_source_selection', $filter, 10, 3 );
				}

				if ( $file_upgrader->install( $slug ) ) {
					$slug   = $file_upgrader->result['destination_name'];
					$result = true;
					if ( $filter ) {
						remove_filter( 'upgrader_source_selection', $filter, 10 );
					}
					$successes++;
				} else {
					$errors++;
				}
			} else {
				// Assume a plugin/theme slug from the WordPress.org repository has been specified.
				$result = $this->install_from_repo( $slug, $assoc_args );

				if ( is_null( $result ) ) {
					$errors++;
				} elseif ( is_wp_error( $result ) ) {
					$key = $result->get_error_code();
					if ( in_array( $key, [ 'plugins_api_failed', 'themes_api_failed' ], true )
						&& ! empty( $result->error_data[ $key ] ) && in_array( $result->error_data[ $key ], [ 'N;', 'b:0;' ], true ) ) {
						WP_CLI::warning( "Couldn't find '$slug' in the WordPress.org {$this->item_type} directory." );
						$errors++;
					} else {
						WP_CLI::warning( "$slug: " . $result->get_error_message() );
						if ( 'already_installed' !== $key ) {
							$errors++;
						}
					}
				} else {
					$successes++;
				}
			}

			// Check extension is available or not.
			$extension = $this->fetcher->get_many( array( $slug ) );

			// If installation goes well $result will be true.
			$allow_activation = $result;

			// Allow installation for installed extension.
			if ( is_wp_error( $result ) && 'already_installed' === $result->get_error_code() ) {
				$allow_activation = true;
			}

			if ( true === $allow_activation && count( $extension ) > 0 ) {
				$this->chained_command = true;
				if ( Utils\get_flag_value( $assoc_args, 'activate-network' ) ) {
					WP_CLI::log( "Network-activating '$slug'..." );
					$this->activate( array( $slug ), array( 'network' => true ) );
				}

				if ( Utils\get_flag_value( $assoc_args, 'activate' ) ) {
					WP_CLI::log( "Activating '$slug'..." );
					$this->activate( array( $slug ) );
				}
				$this->chained_command = false;
			}
		}
		Utils\report_batch_operation_results( $this->item_type, 'install', count( $args ), $successes, $errors );
	}

	/**
	 * Prepare an API response for downloading a particular version of an item.
	 *
	 * @param object $response wordpress.org API response
	 * @param string $version The desired version of the package
	 */
	protected static function alter_api_response( $response, $version ) {
		if ( $response->version === $version ) {
			return;
		}

		// WordPress.org forces https, but still sometimes returns http
		// See https://twitter.com/nacin/status/512362694205140992
		$response->download_link = str_replace( 'http://', 'https://', $response->download_link );

		list( $link ) = explode( $response->slug, $response->download_link );

		if ( false !== strpos( $response->download_link, '/theme/' ) ) {
			$download_type = 'theme';
		} elseif ( false !== strpos( $response->download_link, '/plugin/' ) ) {
			$download_type = 'plugin';
		} else {
			$download_type = 'plugin/theme';
		}

		if ( 'dev' === $version ) {
			$response->download_link = $link . $response->slug . '.zip';
			$response->version       = 'Development Version';
		} else {
			$response->download_link = $link . $response->slug . '.' . $version . '.zip';
			$response->version       = $version;

			// Check if the requested version exists.
			$response      = wp_remote_head( $response->download_link );
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== (int) $response_code ) {
				if ( is_wp_error( $response ) ) {
					$error_msg = $response->get_error_message();
				} else {
					$error_msg = sprintf( 'HTTP code %d', $response_code );
				}
				WP_CLI::error(
					sprintf(
						"Can't find the requested %s's version %s in the WordPress.org %s repository (%s).",
						$download_type,
						$version,
						$download_type,
						$error_msg
					)
				);
			}
		}
	}

	protected function get_upgrader( $assoc_args ) {
		$force          = (bool) Utils\get_flag_value( $assoc_args, 'force', false );
		$insecure       = (bool) Utils\get_flag_value( $assoc_args, 'insecure', false );
		$upgrader_class = $this->get_upgrader_class( $force );
		return Utils\get_upgrader( $upgrader_class, $insecure );
	}

	protected function update_many( $args, $assoc_args ) {
		call_user_func( $this->upgrade_refresh );

		if ( ! empty( $assoc_args['format'] ) && in_array( $assoc_args['format'], [ 'json', 'csv' ], true ) ) {
			$logger = new Loggers\Quiet( WP_CLI::get_runner()->in_color() );
			WP_CLI::set_logger( $logger );
		}

		if ( ! Utils\get_flag_value( $assoc_args, 'all' ) && empty( $args ) ) {
			WP_CLI::error( "Please specify one or more {$this->item_type}s, or use --all." );
		}

		if ( Utils\get_flag_value( $assoc_args, 'minor' ) && Utils\get_flag_value( $assoc_args, 'patch' ) ) {
			WP_CLI::error( '--minor and --patch cannot be used together.' );
		}

		$items = $this->get_item_list();

		$errors = 0;
		if ( ! Utils\get_flag_value( $assoc_args, 'all' ) ) {
			$items  = $this->filter_item_list( $items, $args );
			$errors = count( $args ) - count( $items );
		}

		$items_to_update = wp_list_filter( $items, [ 'update' => true ] );

		$minor = (bool) Utils\get_flag_value( $assoc_args, 'minor', false );
		$patch = (bool) Utils\get_flag_value( $assoc_args, 'patch', false );

		if ( 'plugin' === $this->item_type
			&& ( $minor || $patch ) ) {
			$type     = $minor ? 'minor' : 'patch';
			$insecure = (bool) Utils\get_flag_value( $assoc_args, 'insecure', false );

			$items_to_update = self::get_minor_or_patch_updates( $items_to_update, $type, $insecure );
		}

		$exclude = Utils\get_flag_value( $assoc_args, 'exclude' );
		if ( isset( $exclude ) ) {
			$exclude_items = explode( ',', trim( $assoc_args['exclude'], ',' ) );
			unset( $assoc_args['exclude'] );
			foreach ( $exclude_items as $item ) {
				if ( 'plugin' === $this->item_type ) {
					$plugin = $this->fetcher->get( $item );
					unset( $items_to_update[ $plugin->file ] );
				} elseif ( 'theme' === $this->item_type ) {
					$theme_root = get_theme_root() . '/' . $item;
					unset( $items_to_update[ $theme_root ] );
				}
			}
		}

		// Check for items to update and remove extensions that have version higher than expected.
		foreach ( $items_to_update as $item_key => $item_info ) {
			if ( static::INVALID_VERSION_MESSAGE === $item_info['update'] ) {
				WP_CLI::warning( "{$item_info['name']}: " . static::INVALID_VERSION_MESSAGE . '.' );
				$errors++;
				unset( $items_to_update[ $item_key ] );
			}
		}

		if ( Utils\get_flag_value( $assoc_args, 'dry-run' ) ) {
			if ( empty( $items_to_update ) ) {
				WP_CLI::log( "No {$this->item_type} updates available." );

				if ( null !== $exclude ) {
					WP_CLI::log( "Skipped updates for: $exclude" );
				}

				return;
			}

			if ( ! empty( $assoc_args['format'] ) && in_array( $assoc_args['format'], [ 'json', 'csv' ], true ) ) {
				Utils\format_items( $assoc_args['format'], $items_to_update, [ 'name', 'status', 'version', 'update_version' ] );
			} elseif ( ! empty( $assoc_args['format'] ) && 'summary' === $assoc_args['format'] ) {
				WP_CLI::log( "Available {$this->item_type} updates:" );
				foreach ( $items_to_update as $item_to_update => $info ) {
					WP_CLI::log( "{$info['title']} update from version {$info['version']} to version {$info['update_version']}" );
				}
			} else {
				WP_CLI::log( "Available {$this->item_type} updates:" );
				Utils\format_items( 'table', $items_to_update, [ 'name', 'status', 'version', 'update_version' ] );
			}

			if ( null !== $exclude ) {
				WP_CLI::log( "Skipped updates for: $exclude" );
			}

			return;
		}

		$result = array();

		// Only attempt to update if there is something to update.
		if ( ! empty( $items_to_update ) ) {
			$cache_manager = WP_CLI::get_http_cache_manager();
			foreach ( $items_to_update as $item ) {
				$cache_manager->whitelist_package( $item['update_package'], $this->item_type, $item['name'], $item['update_version'] );
			}
			$upgrader = $this->get_upgrader( $assoc_args );
			// Ensure the upgrader uses the download offer present in each item.
			$transient_filter = function( $transient ) use ( $items_to_update ) {
				foreach ( $items_to_update as $name => $item_data ) {
					if ( isset( $transient->response[ $name ] ) ) {
						$transient->response[ $name ]->new_version = $item_data['update_version'];
						$transient->response[ $name ]->package     = $item_data['update_package'];
					}
				}
				return $transient;
			};
			add_filter( 'site_transient_' . $this->upgrade_transient, $transient_filter, 999 );
			$result = $upgrader->bulk_upgrade( wp_list_pluck( $items_to_update, 'update_id' ) );
			remove_filter( 'site_transient_' . $this->upgrade_transient, $transient_filter, 999 );
		}

		// Let the user know the results.
		$num_to_update = count( $items_to_update );
		$num_updated   = count(
			array_filter(
				$result,
				static function ( $result ) {
					return $result && ! is_wp_error( $result );
				}
			)
		);

		if ( $num_to_update > 0 ) {
			if ( ! empty( $assoc_args['format'] ) && 'summary' === $assoc_args['format'] ) {
				foreach ( $items_to_update as $item_to_update => $info ) {
					$message = null !== $result[ $info['update_id'] ] ? 'updated successfully' : 'did not update';
					WP_CLI::log( "{$info['title']} {$message} from version {$info['version']} to version {$info['update_version']}" );
				}
			} else {
				$status = array();
				foreach ( $items_to_update as $item_to_update => $info ) {
					$status[ $item_to_update ] = [
						'name'        => $info['name'],
						'old_version' => $info['version'],
						'new_version' => $info['update_version'],
						'status'      => ( null !== $result[ $info['update_id'] ] && ! is_wp_error( $result[ $info['update_id'] ] ) ) ? 'Updated' : 'Error',
					];
					if ( null === $result[ $info['update_id'] ] || is_wp_error( $result[ $info['update_id'] ] ) ) {
						$errors++;
					}
				}

				$format = 'table';
				if ( ! empty( $assoc_args['format'] ) && in_array( $assoc_args['format'], [ 'json', 'csv' ], true ) ) {
					$format = $assoc_args['format'];
				}

				Utils\format_items( $format, $status, [ 'name', 'old_version', 'new_version', 'status' ] );
			}
		}

		$total_updated = Utils\get_flag_value( $assoc_args, 'all' ) ? $num_to_update : count( $args );
		Utils\report_batch_operation_results( $this->item_type, 'update', $total_updated, $num_updated, $errors );
		if ( null !== $exclude ) {
			WP_CLI::log( "Skipped updates for: $exclude" );
		}
	}

	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore -- Whitelisting to provide backward compatibility to classes possibly extending this class.
	protected function _list( $_, $assoc_args ) {

		// Force WordPress to check for updates if `--skip-update-check` is not passed.
		if ( false === (bool) Utils\get_flag_value( $assoc_args, 'skip-update-check', false ) ) {
			call_user_func( $this->upgrade_refresh );
		}

		$all_items = $this->get_all_items();

		if ( ! is_array( $all_items ) ) {
			WP_CLI::error( "No {$this->item_type}s found." );
		}

		foreach ( $all_items as $key => &$item ) {

			if ( empty( $item['version'] ) ) {
				$item['version'] = '';
			}

			foreach ( $item as $field => &$value ) {
				if ( true === $value ) {
					$value = 'available';
				} elseif ( false === $value ) {
					$value = 'none';
				}
			}

			foreach ( $this->obj_fields as $field ) {
				if ( ! array_key_exists( $field, $assoc_args ) ) {
					continue;
				}

				// This can be either a value to filter by or a comma-separated list of values.
				// Also, it is not forbidden for a value to contain a comma (in which case we can filter only by one).
				$field_filter = $assoc_args[ $field ];
				if (
					$item[ $field ] !== $field_filter
					&& ! in_array( $item[ $field ], array_map( 'trim', explode( ',', $field_filter ) ), true )
				) {
					unset( $all_items[ $key ] );
				}
			}
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $all_items );
	}

	/**
	 * Check whether an item has an update available or not.
	 *
	 * @param string $slug The plugin/theme slug
	 *
	 * @return bool
	 */
	protected function has_update( $slug ) {
		$update_list = get_site_transient( $this->upgrade_transient );

		return isset( $update_list->response[ $slug ] );
	}

	/**
	 * Get the available update info
	 *
	 * @return mixed
	 */
	protected function get_update_info() {
		return get_site_transient( $this->upgrade_transient );
	}

	private $map = [
		'short' => [
			'inactive'       => 'I',
			'active'         => 'A',
			'active-network' => 'N',
			'must-use'       => 'M',
			'parent'         => 'P',
			'dropin'         => 'D',
		],
		'long'  => [
			'inactive'       => 'Inactive',
			'active'         => 'Active',
			'active-network' => 'Network Active',
			'must-use'       => 'Must Use',
			'parent'         => 'Parent',
			'dropin'         => 'Drop-In',
		],
	];

	protected function format_status( $status, $format ) {
		return $this->get_color( $status ) . $this->map[ $format ][ $status ];
	}

	private function get_color( $status ) {
		static $colors = [
			'inactive'       => '',
			'active'         => '%g',
			'active-network' => '%g',
			'must-use'       => '%c',
			'parent'         => '%p',
			'dropin'         => '%B',
		];

		return $colors[ $status ];
	}

	/**
	 * Get the minor or patch version for plugins with available updates
	 *
	 * @param array  $items    Plugins with updates.
	 * @param string $type     Either 'minor' or 'patch'.
	 * @param bool   $insecure Whether to retry without certificate validation on TLS handshake failure.
	 * @return array
	 */
	private function get_minor_or_patch_updates( $items, $type, $insecure ) {
		$wp_org_api = new WpOrgApi( [ 'insecure' => $insecure ] );
		foreach ( $items as $i => $item ) {
			try {
				$data = $wp_org_api->get_plugin_info( $item['name'] );
			} catch ( Exception $exception ) {
				unset( $items[ $i ] );
				continue;
			}
			// No minor or patch versions to access.
			if ( empty( $data['versions'] ) ) {
				unset( $items[ $i ] );
				continue;
			}
			$update_version = false;
			$update_package = false;
			foreach ( $data['versions'] as $version => $download_link ) {
				$update_type = Utils\get_named_sem_ver( $version, $item['version'] );
				// Compared version must be older.
				if ( ! $update_type ) {
					continue;
				}
				// Only permit 'patch' for 'patch'.
				if ( 'patch' === $type && 'patch' !== $update_type ) {
					continue;
				}
				// Permit 'minor' or 'patch' for 'minor' phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- False positive.
				if ( 'minor' === $type && ! in_array( $update_type, array( 'minor', 'patch' ), true ) ) {
					continue;
				}
				if ( $update_version && ! Comparator::greaterThan( $version, $update_version ) ) {
					continue;
				}
				$update_version = $version;
				$update_package = $download_link;
			}
			// If there's not a matching version, bail on updates.
			if ( ! $update_version ) {
				unset( $items[ $i ] );
				continue;
			}
			$items[ $i ]['update_version'] = $update_version;
			$items[ $i ]['update_package'] = $update_package;
		}
		return $items;
	}

	/**
	 * Search wordpress.org repo.
	 *
	 * @param  array $args       A arguments array containing the search term in the first element.
	 * @param  array $assoc_args Data passed in from command.
	 */
	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore -- Whitelisting to provide backward compatibility to classes possibly extending this class.
	protected function _search( $args, $assoc_args ) {
		$term = $args[0];

		$defaults   = [
			'per-page' => 10,
			'page'     => 1,
			'fields'   => implode( ',', [ 'name', 'slug', 'rating' ] ),
		];
		$assoc_args = array_merge( $defaults, $assoc_args );
		$fields     = array();
		foreach ( explode( ',', $assoc_args['fields'] ) as $field ) {
			$fields[ $field ] = true;
		}

		$format    = ! empty( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';
		$formatter = $this->get_formatter( $assoc_args );

		$api_args = [
			'per_page' => (int) $assoc_args['per-page'],
			'page'     => (int) $assoc_args['page'],
			'search'   => $term,
			'fields'   => $fields,
		];

		if ( 'plugin' === $this->item_type ) {
			$api = plugins_api( 'query_plugins', $api_args );
		} else {
			$api = themes_api( 'query_themes', $api_args );
		}

		if ( is_wp_error( $api ) ) {
			WP_CLI::error( $api->get_error_message() . __( ' Try again' ) );
		}

		$plural = $this->item_type . 's';

		if ( ! isset( $api->$plural ) ) {
			WP_CLI::error( __( 'API error. Try Again.' ) );
		}

		$items = $api->$plural;

		// Add `url` for plugin or theme on wordpress.org.
		foreach ( $items as $index => $item_object ) {
			if ( $item_object instanceof \stdClass ) {
				$item_object->url = "https://wordpress.org/{$plural}/{$item_object->slug}/";
			}
		}

		if ( 'table' === $format ) {
			$count = Utils\get_flag_value( $api->info, 'results', 'unknown' );
			WP_CLI::success( sprintf( 'Showing %s of %s %s.', count( $items ), $count, $plural ) );
		}

		$formatter->display_items( $items );
	}

	protected function get_formatter( &$assoc_args ) {
		return new \WP_CLI\Formatter( $assoc_args, $this->obj_fields, $this->item_type );
	}

	/**
	 * Error handler to ignore failures on accessing SSL "https://api.wordpress.org/themes/update-check/1.1/" in `wp_update_themes()`
	 * and "https://api.wordpress.org/plugins/update-check/1.1/" in `wp_update_plugins()` which seem to occur intermittently.
	 */
	public static function error_handler( $errno, $errstr, $errfile, $errline, $errcontext = null ) {
		// If ignoring E_USER_WARNING | E_USER_NOTICE, default.
		if ( ! ( error_reporting() & $errno ) ) {
			return false;
		}
		// If not in "wp-includes/update.php", default.
		$update_php = 'wp-includes/update.php';
		if ( 0 !== substr_compare( $errfile, $update_php, -strlen( $update_php ) ) ) {
			return false;
		}
		// Else assume it's in `wp_update_themes()` or `wp_update_plugins()` and just ignore it.
		return true;
	}

	/**
	 * Retrieves PHP_URL_HOST component from URL.
	 *
	 * @param int $component The component to retrieve.
	 *
	 * @return string
	 */
	private function parse_url_host_component( $url, $component ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- parse_url will only be used in absence of wp_parse_url.
		return function_exists( 'wp_parse_url' ) ? wp_parse_url( $url, $component ) : parse_url( $url, $component );
	}

}
