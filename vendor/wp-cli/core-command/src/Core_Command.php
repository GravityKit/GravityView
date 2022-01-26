<?php

use Composer\Semver\Comparator;
use WP_CLI\Extractor;
use WP_CLI\Iterators\Table as TableIterator;
use WP_CLI\Utils;
use WP_CLI\Formatter;
use WP_CLI\WpOrgApi;

/**
 * Downloads, installs, updates, and manages a WordPress installation.
 *
 * ## EXAMPLES
 *
 *     # Download WordPress core
 *     $ wp core download --locale=nl_NL
 *     Downloading WordPress 4.5.2 (nl_NL)...
 *     md5 hash verified: c5366d05b521831dd0b29dfc386e56a5
 *     Success: WordPress downloaded.
 *
 *     # Install WordPress
 *     $ wp core install --url=example.com --title=Example --admin_user=supervisor --admin_password=strongpassword --admin_email=info@example.com
 *     Success: WordPress installed successfully.
 *
 *     # Display the WordPress version
 *     $ wp core version
 *     4.5.2
 *
 * @package wp-cli
 */
class Core_Command extends WP_CLI_Command {

	/**
	 * Checks for WordPress updates via Version Check API.
	 *
	 * Lists the most recent versions when there are updates available,
	 * or success message when up to date.
	 *
	 * ## OPTIONS
	 *
	 * [--minor]
	 * : Compare only the first two parts of the version number.
	 *
	 * [--major]
	 * : Compare only the first part of the version number.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each update.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields. Defaults to version,update_type,package_url.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - count
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp core check-update
	 *     +---------+-------------+-------------------------------------------------------------+
	 *     | version | update_type | package_url                                                 |
	 *     +---------+-------------+-------------------------------------------------------------+
	 *     | 4.5.2   | major       | https://downloads.wordpress.org/release/wordpress-4.5.2.zip |
	 *     +---------+-------------+-------------------------------------------------------------+
	 *
	 * @subcommand check-update
	 */
	public function check_update( $_, $assoc_args ) {

		$updates = $this->get_updates( $assoc_args );
		if ( $updates ) {
			$updates   = array_reverse( $updates );
			$formatter = new Formatter(
				$assoc_args,
				[ 'version', 'update_type', 'package_url' ]
			);
			$formatter->display_items( $updates );
		} elseif ( empty( $assoc_args['format'] ) || 'table' === $assoc_args['format'] ) {
			WP_CLI::success( 'WordPress is at the latest version.' );
		}
	}

	/**
	 * Downloads core WordPress files.
	 *
	 * Downloads and extracts WordPress core files to the specified path. Uses
	 * current directory when no path is specified. Downloaded build is verified
	 * to have the correct md5 and then cached to the local filesytem.
	 * Subsequent uses of command will use the local cache if it still exists.
	 *
	 * ## OPTIONS
	 *
	 * [<download-url>]
	 * : Download directly from a provided URL instead of fetching the URL from the wordpress.org servers.
	 *
	 * [--path=<path>]
	 * : Specify the path in which to install WordPress. Defaults to current
	 * directory.
	 *
	 * [--locale=<locale>]
	 * : Select which language you want to download.
	 *
	 * [--version=<version>]
	 * : Select which version you want to download. Accepts a version number, 'latest' or 'nightly'.
	 *
	 * [--skip-content]
	 * : Download WP without the default themes and plugins.
	 *
	 * [--force]
	 * : Overwrites existing files, if present.
	 *
	 * [--insecure]
	 * : Retry download without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp core download --locale=nl_NL
	 *     Downloading WordPress 4.5.2 (nl_NL)...
	 *     md5 hash verified: c5366d05b521831dd0b29dfc386e56a5
	 *     Success: WordPress downloaded.
	 *
	 * @when before_wp_load
	 */
	public function download( $args, $assoc_args ) {

		$download_dir = ! empty( $assoc_args['path'] )
			? ( rtrim( $assoc_args['path'], '/\\' ) . '/' )
			: ABSPATH;

		$wordpress_present = is_readable( $download_dir . 'wp-load.php' );

		if ( $wordpress_present && ! Utils\get_flag_value( $assoc_args, 'force' ) ) {
			WP_CLI::error( 'WordPress files seem to already be present here.' );
		}

		if ( ! is_dir( $download_dir ) ) {
			if ( ! is_writable( dirname( $download_dir ) ) ) {
				WP_CLI::error( "Insufficient permission to create directory '{$download_dir}'." );
			}

			WP_CLI::log( "Creating directory '{$download_dir}'." );
			if ( ! @mkdir( $download_dir, 0777, true /*recursive*/ ) ) {
				$error = error_get_last();
				WP_CLI::error( "Failed to create directory '{$download_dir}': {$error['message']}." );
			}
		}

		if ( ! is_writable( $download_dir ) ) {
			WP_CLI::error( "'{$download_dir}' is not writable by current user." );
		}

		$locale       = (string) Utils\get_flag_value( $assoc_args, 'locale', 'en_US' );
		$skip_content = (bool) Utils\get_flag_value( $assoc_args, 'skip-content', false );
		$insecure     = (bool) Utils\get_flag_value( $assoc_args, 'insecure', false );

		$download_url = array_shift( $args );
		$from_url     = ! empty( $download_url );

		if ( $from_url ) {
			$version = null;
			if ( isset( $assoc_args['version'] ) ) {
				WP_CLI::error( 'Version option is not available for URL downloads.' );
			}
			if ( $skip_content || 'en_US' !== $locale ) {
				WP_CLI::error( 'Skip content and locale options are not available for URL downloads.' );
			}
		} elseif ( isset( $assoc_args['version'] ) && 'latest' !== $assoc_args['version'] ) {
			$version = $assoc_args['version'];
			if ( in_array( strtolower( $version ), [ 'trunk', 'nightly' ], true ) ) {
				$version = 'nightly';
			}

			// Nightly builds and skip content are only available in .zip format.
			$extension = ( ( 'nightly' === $version ) || $skip_content )
				? 'zip'
				: 'tar.gz';

			$download_url = $this->get_download_url( $version, $locale, $extension );
		} else {
			try {
				$offer = ( new WpOrgApi( [ 'insecure' => $insecure ] ) )
					->get_core_download_offer( $locale );
			} catch ( Exception $exception ) {
				WP_CLI::error( $exception );
			}
			if ( ! $offer ) {
				WP_CLI::error( "The requested locale ({$locale}) was not found." );
			}
			$version      = $offer['current'];
			$download_url = $offer['download'];
			if ( ! $skip_content ) {
				$download_url = str_replace( '.zip', '.tar.gz', $download_url );
			}
		}

		if ( 'nightly' === $version && 'en_US' !== $locale ) {
			WP_CLI::error( 'Nightly builds are only available for the en_US locale.' );
		}

		$from_version = '';
		if ( file_exists( $download_dir . 'wp-includes/version.php' ) ) {
			$wp_details   = self::get_wp_details( $download_dir );
			$from_version = $wp_details['wp_version'];
		}

		if ( $from_url ) {
			WP_CLI::log( "Downloading from {$download_url} ..." );
		} else {
			WP_CLI::log( "Downloading WordPress {$version} ({$locale})..." );
		}

		$path_parts = pathinfo( $download_url );
		$extension  = 'tar.gz';
		if ( 'zip' === $path_parts['extension'] ) {
			$extension = 'zip';
			if ( ! class_exists( 'ZipArchive' ) ) {
				WP_CLI::error( 'Extracting a zip file requires ZipArchive.' );
			}
		}

		if ( $skip_content && 'zip' !== $extension ) {
			WP_CLI::error( 'Skip content is only available for ZIP files.' );
		}

		$cache = WP_CLI::get_cache();
		if ( $from_url ) {
			$cache_file = null;
		} else {
			$cache_key  = "core/wordpress-{$version}-{$locale}.{$extension}";
			$cache_file = $cache->has( $cache_key );
		}

		$bad_cache = false;
		if ( $cache_file ) {
			WP_CLI::log( "Using cached file '{$cache_file}'..." );
			$skip_content_cache_file = $skip_content ? self::strip_content_dir( $cache_file ) : null;
			try {
				Extractor::extract( $skip_content_cache_file ?: $cache_file, $download_dir );
			} catch ( Exception $exception ) {
				WP_CLI::warning( 'Extraction failed, downloading a new copy...' );
				$bad_cache = true;
			}
		}

		if ( ! $cache_file || $bad_cache ) {
			// We need to use a temporary file because piping from cURL to tar is flaky
			// on MinGW (and probably in other environments too).
			$temp = Utils\get_temp_dir() . uniqid( 'wp_' ) . ".{$extension}";
			register_shutdown_function(
				function () use ( $temp ) {
					if ( file_exists( $temp ) ) {
						unlink( $temp );
					}
				}
			);

			$headers = [ 'Accept' => 'application/json' ];
			$options = [
				'timeout'  => 600,  // 10 minutes ought to be enough for everybody
				'filename' => $temp,
				'insecure' => $insecure,
			];

			$response = Utils\http_request( 'GET', $download_url, null, $headers, $options );

			if ( 404 === (int) $response->status_code ) {
				WP_CLI::error( 'Release not found. Double-check locale or version.' );
			} elseif ( 20 !== (int) substr( $response->status_code, 0, 2 ) ) {
				WP_CLI::error( "Couldn't access download URL (HTTP code {$response->status_code})." );
			}

			if ( 'nightly' !== $version ) {
				unset( $options['filename'] );
				$md5_response = Utils\http_request( 'GET', $download_url . '.md5', null, [], $options );
				if ( $md5_response->status_code >= 200 && $md5_response->status_code < 300 ) {
					$md5_file = md5_file( $temp );

					if ( $md5_file === $md5_response->body ) {
						WP_CLI::log( 'md5 hash verified: ' . $md5_file );
					} else {
						WP_CLI::error( "md5 hash for download ({$md5_file}) is different than the release hash ({$md5_response->body})." );
					}
				} else {
					WP_CLI::warning( "Couldn't access md5 hash for release ({$download_url}.md5, HTTP code {$md5_response->status_code})." );
				}
			} else {
				WP_CLI::warning( 'md5 hash checks are not available for nightly downloads.' );
			}

			$skip_content_temp = $skip_content ? self::strip_content_dir( $temp ) : null;

			try {
				Extractor::extract( $skip_content_temp ?: $temp, $download_dir );
			} catch ( Exception $exception ) {
				WP_CLI::error( "Couldn't extract WordPress archive. {$exception->getMessage()}" );
			}

			// Do not use the cache for nightly builds or for downloaded URLs
			// (the URL could be something like "latest.zip" or "nightly.zip").
			if ( ! $from_url && 'nightly' !== $version ) {
				$cache->import( $cache_key, $temp );
			}
		}

		if ( $wordpress_present ) {
			$this->cleanup_extra_files( $from_version, $version, $locale, $insecure );
		}

		WP_CLI::success( 'WordPress downloaded.' );
	}

	/**
	 * Checks if WordPress is installed.
	 *
	 * Determines whether WordPress is installed by checking if the standard
	 * database tables are installed. Doesn't produce output; uses exit codes
	 * to communicate whether WordPress is installed.
	 *
	 * [--network]
	 * : Check if this is a multisite installation.
	 *
	 * ## EXAMPLES
	 *
	 *     # Check whether WordPress is installed; exit status 0 if installed, otherwise 1
	 *     $ wp core is-installed
	 *     $ echo $?
	 *     1
	 *
	 *     # Bash script for checking whether WordPress is installed or not
	 *     if ! wp core is-installed; then
	 *         wp core install
	 *     fi
	 *
	 * @subcommand is-installed
	 */
	public function is_installed( $args, $assoc_args ) {
		if ( is_blog_installed()
			&& ( ! Utils\get_flag_value( $assoc_args, 'network' )
				|| is_multisite() ) ) {
			WP_CLI::halt( 0 );
		}

		WP_CLI::halt( 1 );
	}

	/**
	 * Runs the standard WordPress installation process.
	 *
	 * Creates the WordPress tables in the database using the URL, title, and
	 * default admin user details provided. Performs the famous 5 minute install
	 * in seconds or less.
	 *
	 * Note: if you've installed WordPress in a subdirectory, then you'll need
	 * to `wp option update siteurl` after `wp core install`. For instance, if
	 * WordPress is installed in the `/wp` directory and your domain is example.com,
	 * then you'll need to run `wp option update siteurl http://example.com/wp` for
	 * your WordPress installation to function properly.
	 *
	 * Note: When using custom user tables (e.g. `CUSTOM_USER_TABLE`), the admin
	 * email and password are ignored if the user_login already exists. If the
	 * user_login doesn't exist, a new user will be created.
	 *
	 * ## OPTIONS
	 *
	 * --url=<url>
	 * : The address of the new site.
	 *
	 * --title=<site-title>
	 * : The title of the new site.
	 *
	 * --admin_user=<username>
	 * : The name of the admin user.
	 *
	 * [--admin_password=<password>]
	 * : The password for the admin user. Defaults to randomly generated string.
	 *
	 * --admin_email=<email>
	 * : The email address for the admin user.
	 *
	 * [--skip-email]
	 * : Don't send an email notification to the new admin user.
	 *
	 * ## EXAMPLES
	 *
	 *     # Install WordPress in 5 seconds
	 *     $ wp core install --url=example.com --title=Example --admin_user=supervisor --admin_password=strongpassword --admin_email=info@example.com
	 *     Success: WordPress installed successfully.
	 *
	 *     # Install WordPress without disclosing admin_password to bash history
	 *     $ wp core install --url=example.com --title=Example --admin_user=supervisor --admin_email=info@example.com --prompt=admin_password < admin_password.txt
	 */
	public function install( $args, $assoc_args ) {
		if ( $this->do_install( $assoc_args ) ) {
			WP_CLI::success( 'WordPress installed successfully.' );
		} else {
			WP_CLI::log( 'WordPress is already installed.' );
		}
	}

	/**
	 * Transforms an existing single-site installation into a multisite installation.
	 *
	 * Creates the multisite database tables, and adds the multisite constants
	 * to wp-config.php.
	 *
	 * For those using WordPress with Apache, remember to update the `.htaccess`
	 * file with the appropriate multisite rewrite rules.
	 *
	 * [Review the multisite documentation](https://codex.wordpress.org/Create_A_Network)
	 * for more details about how multisite works.
	 *
	 * ## OPTIONS
	 *
	 * [--title=<network-title>]
	 * : The title of the new network.
	 *
	 * [--base=<url-path>]
	 * : Base path after the domain name that each site url will start with.
	 * ---
	 * default: /
	 * ---
	 *
	 * [--subdomains]
	 * : If passed, the network will use subdomains, instead of subdirectories. Doesn't work with 'localhost'.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp core multisite-convert
	 *     Set up multisite database tables.
	 *     Added multisite constants to wp-config.php.
	 *     Success: Network installed. Don't forget to set up rewrite rules.
	 *
	 * @subcommand multisite-convert
	 * @alias install-network
	 */
	public function multisite_convert( $args, $assoc_args ) {
		if ( is_multisite() ) {
			WP_CLI::error( 'This already is a multisite installation.' );
		}

		$assoc_args = self::set_multisite_defaults( $assoc_args );
		if ( ! isset( $assoc_args['title'] ) ) {
			// translators: placeholder is blog name
			$assoc_args['title'] = sprintf( _x( '%s Sites', 'Default network name' ), get_option( 'blogname' ) );
		}

		if ( $this->multisite_convert_( $assoc_args ) ) {
			WP_CLI::success( "Network installed. Don't forget to set up rewrite rules (and a .htaccess file, if using Apache)." );
		}
	}

	/**
	 * Installs WordPress multisite from scratch.
	 *
	 * Creates the WordPress tables in the database using the URL, title, and
	 * default admin user details provided. Then, creates the multisite tables
	 * in the database and adds multisite constants to the wp-config.php.
	 *
	 * For those using WordPress with Apache, remember to update the `.htaccess`
	 * file with the appropriate multisite rewrite rules.
	 *
	 * ## OPTIONS
	 *
	 * [--url=<url>]
	 * : The address of the new site.
	 *
	 * [--base=<url-path>]
	 * : Base path after the domain name that each site url in the network will start with.
	 * ---
	 * default: /
	 * ---
	 *
	 * [--subdomains]
	 * : If passed, the network will use subdomains, instead of subdirectories. Doesn't work with 'localhost'.
	 *
	 * --title=<site-title>
	 * : The title of the new site.
	 *
	 * --admin_user=<username>
	 * : The name of the admin user.
	 * ---
	 * default: admin
	 * ---
	 *
	 * [--admin_password=<password>]
	 * : The password for the admin user. Defaults to randomly generated string.
	 *
	 * --admin_email=<email>
	 * : The email address for the admin user.
	 *
	 * [--skip-email]
	 * : Don't send an email notification to the new admin user.
	 *
	 * [--skip-config]
	 * : Don't add multisite constants to wp-config.php.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp core multisite-install --title="Welcome to the WordPress" \
	 *     > --admin_user="admin" --admin_password="password" \
	 *     > --admin_email="user@example.com"
	 *     Single site database tables already present.
	 *     Set up multisite database tables.
	 *     Added multisite constants to wp-config.php.
	 *     Success: Network installed. Don't forget to set up rewrite rules.
	 *
	 * @subcommand multisite-install
	 */
	public function multisite_install( $args, $assoc_args ) {
		if ( $this->do_install( $assoc_args ) ) {
			WP_CLI::log( 'Created single site database tables.' );
		} else {
			WP_CLI::log( 'Single site database tables already present.' );
		}

		$assoc_args = self::set_multisite_defaults( $assoc_args );
		// translators: placeholder is user supplied title
		$assoc_args['title'] = sprintf( _x( '%s Sites', 'Default network name' ), $assoc_args['title'] );

		// Overwrite runtime args, to avoid mismatches.
		$consts_to_args = [
			'SUBDOMAIN_INSTALL'    => 'subdomains',
			'PATH_CURRENT_SITE'    => 'base',
			'SITE_ID_CURRENT_SITE' => 'site_id',
			'BLOG_ID_CURRENT_SITE' => 'blog_id',
		];

		foreach ( $consts_to_args as $const => $arg ) {
			if ( defined( $const ) ) {
				$assoc_args[ $arg ] = constant( $const );
			}
		}

		if ( ! $this->multisite_convert_( $assoc_args ) ) {
			return;
		}

		// Do the steps that were skipped by populate_network(),
		// which checks is_multisite().
		if ( is_multisite() ) {
			$site_user = get_user_by( 'email', $assoc_args['admin_email'] );
			self::add_site_admins( $site_user );
			$domain = self::get_clean_basedomain();
			self::create_initial_blog(
				$assoc_args['site_id'],
				$assoc_args['blog_id'],
				$domain,
				$assoc_args['base'],
				$assoc_args['subdomains'],
				$site_user
			);
		}

		WP_CLI::success( "Network installed. Don't forget to set up rewrite rules (and a .htaccess file, if using Apache)." );
	}

	private static function set_multisite_defaults( $assoc_args ) {
		$defaults = [
			'subdomains' => false,
			'base'       => '/',
			'site_id'    => 1,
			'blog_id'    => 1,
		];

		return array_merge( $defaults, $assoc_args );
	}

	private function do_install( $assoc_args ) {
		if ( is_blog_installed() ) {
			return false;
		}

		if ( true === Utils\get_flag_value( $assoc_args, 'skip-email' ) ) {
			if ( ! function_exists( 'wp_new_blog_notification' ) ) {
				function wp_new_blog_notification() {
					// Silence is golden
				}
			}
			// WP 4.9.0 - skip "Notice of Admin Email Change" email as well (https://core.trac.wordpress.org/ticket/39117).
			add_filter( 'send_site_admin_email_change_email', '__return_false' );
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$defaults = [
			'title'          => '',
			'admin_user'     => '',
			'admin_email'    => '',
			'admin_password' => '',
		];

		$args = wp_parse_args( $assoc_args, $defaults );

		// Support prompting for the `--url=<url>`,
		// which is normally a runtime argument
		if ( isset( $assoc_args['url'] ) ) {
			WP_CLI::set_url( $assoc_args['url'] );
		}

		$public   = true;
		$password = empty( $args['admin_password'] )
			? wp_generate_password( 18 )
			: $args['admin_password'];

		if ( ! is_email( $args['admin_email'] ) ) {
			WP_CLI::error( "The '{$args['admin_email']}' email address is invalid." );
		}

		$result = wp_install(
			$args['title'],
			$args['admin_user'],
			$args['admin_email'],
			$public,
			'',
			$password
		);

		if ( is_wp_error( $result ) ) {
			$reason = WP_CLI::error_to_string( $result );
			WP_CLI::error( "Installation failed ({$reason})." );
		}

		if ( ! empty( $GLOBALS['wpdb']->last_error ) ) {
			WP_CLI::error( 'Installation produced database errors, and may have partially or completely failed.' );
		}

		if ( empty( $args['admin_password'] ) ) {
			WP_CLI::log( "Admin password: {$result['password']}" );
		}

		// Confirm the uploads directory exists
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			WP_CLI::warning( $upload_dir['error'] );
		}

		return true;
	}

	private function multisite_convert_( $assoc_args ) {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$domain = self::get_clean_basedomain();
		if ( 'localhost' === $domain && ! empty( $assoc_args['subdomains'] ) ) {
			WP_CLI::error( "Multisite with subdomains cannot be configured when domain is 'localhost'." );
		}

		// need to register the multisite tables manually for some reason
		foreach ( $wpdb->tables( 'ms_global' ) as $table => $prefixed_table ) {
			$wpdb->$table = $prefixed_table;
		}

		install_network();

		$result = populate_network(
			$assoc_args['site_id'],
			$domain,
			get_option( 'admin_email' ),
			$assoc_args['title'],
			$assoc_args['base'],
			$assoc_args['subdomains']
		);

		$site_id = $wpdb->get_var( "SELECT id FROM $wpdb->site" );
		$site_id = ( null === $site_id ) ? 1 : (int) $site_id;

		if ( true === $result ) {
			WP_CLI::log( 'Set up multisite database tables.' );
		} elseif ( is_wp_error( $result ) ) {
			switch ( $result->get_error_code() ) {

				case 'siteid_exists':
					WP_CLI::log( $result->get_error_message() );
					return false;

				case 'no_wildcard_dns':
					WP_CLI::warning( __( 'Wildcard DNS may not be configured correctly.' ) );
					break;

				default:
					WP_CLI::error( $result );
			}
		}

		// delete_site_option() cleans the alloptions cache to prevent dupe option
		delete_site_option( 'upload_space_check_disabled' );
		update_site_option( 'upload_space_check_disabled', 1 );

		if ( ! is_multisite() ) {
			$subdomain_export = Utils\get_flag_value( $assoc_args, 'subdomains' ) ? 'true' : 'false';
			$ms_config        = <<<EOT
define( 'WP_ALLOW_MULTISITE', true );
define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', {$subdomain_export} );
\$base = '{$assoc_args['base']}';
define( 'DOMAIN_CURRENT_SITE', '{$domain}' );
define( 'PATH_CURRENT_SITE', '{$assoc_args['base']}' );
define( 'SITE_ID_CURRENT_SITE', {$site_id} );
define( 'BLOG_ID_CURRENT_SITE', 1 );
EOT;

			$wp_config_path = Utils\locate_wp_config();
			if ( true === Utils\get_flag_value( $assoc_args, 'skip-config' ) ) {
				WP_CLI::log( "Addition of multisite constants to 'wp-config.php' skipped. You need to add them manually:\n{$ms_config}" );
			} elseif ( is_writable( $wp_config_path ) && self::modify_wp_config( $ms_config ) ) {
				WP_CLI::log( "Added multisite constants to 'wp-config.php'." );
			} else {
				WP_CLI::warning( "Multisite constants could not be written to 'wp-config.php'. You may need to add them manually:\n{$ms_config}" );
			}
		} else {
			/* Multisite constants are defined, therefore we already have an empty site_admins site meta.
			 *
			 * Code based on parts of delete_network_option. */
			$rows = $wpdb->get_results( "SELECT meta_id, site_id FROM {$wpdb->sitemeta} WHERE meta_key = 'site_admins' AND meta_value = ''" );

			foreach ( $rows as $row ) {
				wp_cache_delete( "{$row->site_id}:site_admins", 'site-options' );

				$wpdb->delete(
					$wpdb->sitemeta,
					[ 'meta_id' => $row->meta_id ]
				);
			}
		}

		return true;
	}

	// copied from populate_network()
	private static function create_initial_blog( $network_id, $blog_id, $domain, $path,
		$subdomain_install, $site_user ) {
		global $wpdb, $current_site, $wp_rewrite;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- This is meant to replace Core functionality.
		$current_site            = new stdClass();
		$current_site->domain    = $domain;
		$current_site->path      = $path;
		$current_site->site_name = ucfirst( $domain );
		$blog_data               = [
			'site_id'    => $network_id,
			'domain'     => $domain,
			'path'       => $path,
			'registered' => current_time( 'mysql' ),
		];
		$wpdb->insert( $wpdb->blogs, $blog_data );
		$current_site->blog_id = $wpdb->insert_id;
		$blog_id               = $wpdb->insert_id;
		update_user_meta( $site_user->ID, 'source_domain', $domain );
		update_user_meta( $site_user->ID, 'primary_blog', $blog_id );

		if ( $subdomain_install ) {
			$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		} else {
			$wp_rewrite->set_permalink_structure( '/blog/%year%/%monthnum%/%day%/%postname%/' );
		}

		flush_rewrite_rules();
	}

	// copied from populate_network()
	private static function add_site_admins( $site_user ) {
		$site_admins = [ $site_user->user_login ];
		$users       = get_users( [ 'fields' => [ 'ID', 'user_login' ] ] );
		if ( $users ) {
			foreach ( $users as $user ) {
				if ( is_super_admin( $user->ID )
					&& ! in_array( $user->user_login, $site_admins, true ) ) {
					$site_admins[] = $user->user_login;
				}
			}
		}

		update_site_option( 'site_admins', $site_admins );
	}

	private static function modify_wp_config( $content ) {
		$wp_config_path = Utils\locate_wp_config();

		$token           = "/* That's all, stop editing!";
		$config_contents = file_get_contents( $wp_config_path );
		if ( false === strpos( $config_contents, $token ) ) {
			return false;
		}

		list( $before, $after ) = explode( $token, $config_contents );

		$content = trim( $content );

		file_put_contents(
			$wp_config_path,
			"{$before}\n\n{$content}\n\n{$token}{$after}"
		);

		return true;
	}

	private static function get_clean_basedomain() {
		$domain = preg_replace( '|https?://|', '', get_option( 'siteurl' ) );
		$slash  = strpos( $domain, '/' );
		if ( false !== $slash ) {
			$domain = substr( $domain, 0, $slash );
		}
		return $domain;
	}

	/**
	 * Displays the WordPress version.
	 *
	 * ## OPTIONS
	 *
	 * [--extra]
	 * : Show extended version information.
	 *
	 * ## EXAMPLES
	 *
	 *     # Display the WordPress version
	 *     $ wp core version
	 *     4.5.2
	 *
	 *     # Display WordPress version along with other information
	 *     $ wp core version --extra
	 *     WordPress version: 4.5.2
	 *     Database revision: 36686
	 *     TinyMCE version:   4.310 (4310-20160418)
	 *     Package language:  en_US
	 *
	 * @when before_wp_load
	 */
	public function version( $args = [], $assoc_args = [] ) {
		$details = self::get_wp_details();

		if ( ! Utils\get_flag_value( $assoc_args, 'extra' ) ) {
			WP_CLI::line( $details['wp_version'] );
			return;
		}

		$match                   = [];
		$found_version           = preg_match( '/(\d)(\d+)-/', $details['tinymce_version'], $match );
		$human_readable_tiny_mce = $found_version ? "{$match[1]}.{$match[2]}" : '';

		echo Utils\mustache_render(
			self::get_template_path( 'versions.mustache' ),
			[
				'wp-version'    => $details['wp_version'],
				'db-version'    => $details['wp_db_version'],
				'local-package' => empty( $details['wp_local_package'] )
					? 'en_US'
					: $details['wp_local_package'],
				'mce-version'   => $human_readable_tiny_mce
					? "{$human_readable_tiny_mce} ({$details['tinymce_version']})"
					: $details['tinymce_version'],
			]
		);
	}

	/**
	 * Gets version information from `wp-includes/version.php`.
	 *
	 * @return array {
	 *     @type string $wp_version The WordPress version.
	 *     @type int $wp_db_version The WordPress DB revision.
	 *     @type string $tinymce_version The TinyMCE version.
	 *     @type string $wp_local_package The TinyMCE version.
	 * }
	 */
	private static function get_wp_details( $abspath = ABSPATH ) {
		$versions_path = $abspath . 'wp-includes/version.php';

		if ( ! is_readable( $versions_path ) ) {
			WP_CLI::error(
				"This does not seem to be a WordPress installation.\n" .
				'Pass --path=`path/to/wordpress` or run `wp core download`.'
			);
		}

		$version_content = file_get_contents( $versions_path, null, null, 6, 2048 );

		$vars   = [ 'wp_version', 'wp_db_version', 'tinymce_version', 'wp_local_package' ];
		$result = [];

		foreach ( $vars as $var_name ) {
			$result[ $var_name ] = self::find_var( $var_name, $version_content );
		}

		return $result;
	}

	/**
	 * Gets the template path based on installation type.
	 */
	private static function get_template_path( $template ) {
		$command_root  = Utils\phar_safe_path( dirname( __DIR__ ) );
		$template_path = "{$command_root}/templates/{$template}";

		if ( ! file_exists( $template_path ) ) {
			WP_CLI::error( "Couldn't find {$template}" );
		}

		return $template_path;
	}

	/**
	 * Searches for the value assigned to variable `$var_name` in PHP code `$code`.
	 *
	 * This is equivalent to matching the `\$VAR_NAME = ([^;]+)` regular expression and returning
	 * the first match either as a `string` or as an `integer` (depending if it's surrounded by
	 * quotes or not).
	 *
	 * @param string $var_name Variable name to search for.
	 * @param string $code PHP code to search in.
	 *
	 * @return int|string|null
	 */
	private static function find_var( $var_name, $code ) {
		$start = strpos( $code, '$' . $var_name . ' = ' );

		if ( ! $start ) {
			return null;
		}

		$start = $start + strlen( $var_name ) + 3;
		$end   = strpos( $code, ';', $start );

		$value = substr( $code, $start, $end - $start );

		return trim( $value, " '" );
	}

	/**
	 * Security copy of the core function with Requests - Gets the checksums for the given version of WordPress.
	 *
	 * @param string $version  Version string to query.
	 * @param string $locale   Locale to query.
	 * @param bool   $insecure Whether to retry without certificate validation on TLS handshake failure.
	 * @return string|array String message on failure. An array of checksums on success.
	 */
	private static function get_core_checksums( $version, $locale, $insecure ) {
		$query = http_build_query( compact( 'version', 'locale' ), null, '&' );
		$url   = "https://api.wordpress.org/core/checksums/1.0/?{$query}";

		$headers = [ 'Accept' => 'application/json' ];
		$options = [
			'timeout'  => 30,
			'insecure' => $insecure,
		];

		$response = Utils\http_request( 'GET', $url, null, $headers, $options );

		if ( ! $response->success || 200 !== (int) $response->status_code ) {
			return "Checksum request '{$url}' failed (HTTP {$response->status_code}).";
		}

		$body = trim( $response->body );
		$body = json_decode( $body, true );

		if ( ! is_array( $body )
			|| ! isset( $body['checksums'] )
			|| ! is_array( $body['checksums'] ) ) {
			return "Checksums not available for WordPress {$version}/{$locale}.";
		}

		return $body['checksums'];
	}

	/**
	 * Updates WordPress to a newer version.
	 *
	 * Defaults to updating WordPress to the latest version.
	 *
	 * If you see "Error: Another update is currently in progress.", you may
	 * need to run `wp option delete core_updater.lock` after verifying another
	 * update isn't actually running.
	 *
	 * ## OPTIONS
	 *
	 * [<zip>]
	 * : Path to zip file to use, instead of downloading from wordpress.org.
	 *
	 * [--minor]
	 * : Only perform updates for minor releases (e.g. update from WP 4.3 to 4.3.3 instead of 4.4.2).
	 *
	 * [--version=<version>]
	 * : Update to a specific version, instead of to the latest version. Alternatively accepts 'nightly'.
	 *
	 * [--force]
	 * : Update even when installed WP version is greater than the requested version.
	 *
	 * [--locale=<locale>]
	 * : Select which language you want to download.
	 *
	 * [--insecure]
	 * : Retry download without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.
	 *
	 * ## EXAMPLES
	 *
	 *     # Update WordPress
	 *     $ wp core update
	 *     Updating to version 4.5.2 (en_US)...
	 *     Downloading update from https://downloads.wordpress.org/release/wordpress-4.5.2-no-content.zip...
	 *     Unpacking the update...
	 *     Cleaning up files...
	 *     No files found that need cleaning up
	 *     Success: WordPress updated successfully.
	 *
	 *     # Update WordPress to latest version of 3.8 release
	 *     $ wp core update --version=3.8 ../latest.zip
	 *     Updating to version 3.8 ()...
	 *     Unpacking the update...
	 *     Cleaning up files...
	 *     File removed: wp-admin/js/tags-box.js
	 *     ...
	 *     File removed: wp-admin/js/updates.min.
	 *     377 files cleaned up
	 *     Success: WordPress updated successfully.
	 *
	 *     # Update WordPress to 3.1 forcefully
	 *     $ wp core update --version=3.1 --force
	 *     Updating to version 3.1 (en_US)...
	 *     Downloading update from https://wordpress.org/wordpress-3.1.zip...
	 *     Unpacking the update...
	 *     Warning: Checksums not available for WordPress 3.1/en_US. Please cleanup files manually.
	 *     Success: WordPress updated successfully.
	 *
	 * @alias upgrade
	 */
	public function update( $args, $assoc_args ) {
		global $wp_version;

		$update   = null;
		$upgrader = 'WP_CLI\\Core\\CoreUpgrader';

		if ( 'trunk' === Utils\get_flag_value( $assoc_args, 'version' ) ) {
			$assoc_args['version'] = 'nightly';
		}

		if ( ! empty( $args[0] ) ) {

			// ZIP path or URL is given
			$upgrader = 'WP_CLI\\Core\\NonDestructiveCoreUpgrader';
			$version  = Utils\get_flag_value( $assoc_args, 'version' );

			$update = (object) [
				'response' => 'upgrade',
				'current'  => $version,
				'download' => $args[0],
				'packages' => (object) [
					'partial'     => null,
					'new_bundled' => null,
					'no_content'  => null,
					'full'        => $args[0],
				],
				'version'  => $version,
				'locale'   => null,
			];

		} elseif ( empty( $assoc_args['version'] ) ) {

			// Update to next release
			wp_version_check();
			$from_api = get_site_transient( 'update_core' );

			if ( Utils\get_flag_value( $assoc_args, 'minor' ) ) {
				foreach ( $from_api->updates as $offer ) {
					$sem_ver = Utils\get_named_sem_ver( $offer->version, $wp_version );
					if ( ! $sem_ver || 'patch' !== $sem_ver ) {
						continue;
					}
					$update = $offer;
					break;
				}
				if ( empty( $update ) ) {
					WP_CLI::success( 'WordPress is at the latest minor release.' );
					return;
				}
			} else {
				if ( ! empty( $from_api->updates ) ) {
					list( $update ) = $from_api->updates;
				}
			}
		} elseif ( Utils\wp_version_compare( $assoc_args['version'], '<' )
			|| 'nightly' === $assoc_args['version']
			|| Utils\get_flag_value( $assoc_args, 'force' ) ) {

			// Specific version is given
			$version = $assoc_args['version'];
			$locale  = Utils\get_flag_value( $assoc_args, 'locale', get_locale() );

			$new_package = $this->get_download_url( $version, $locale );

			$update = (object) [
				'response' => 'upgrade',
				'current'  => $assoc_args['version'],
				'download' => $new_package,
				'packages' => (object) [
					'partial'     => null,
					'new_bundled' => null,
					'no_content'  => null,
					'full'        => $new_package,
				],
				'version'  => $version,
				'locale'   => $locale,
			];

		}

		if ( ! empty( $update )
			&& ( $update->version !== $wp_version
			|| Utils\get_flag_value( $assoc_args, 'force' ) ) ) {

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			if ( $update->version ) {
				WP_CLI::log( "Updating to version {$update->version} ({$update->locale})..." );
			} else {
				WP_CLI::log( 'Starting update...' );
			}

			$from_version = $wp_version;
			$insecure     = (bool) Utils\get_flag_value( $assoc_args, 'insecure', false );

			$GLOBALS['wpcli_core_update_obj'] = $update;
			$result                           = Utils\get_upgrader( $upgrader, $insecure )->upgrade( $update );
			unset( $GLOBALS['wpcli_core_update_obj'] );

			if ( is_wp_error( $result ) ) {
				$message = WP_CLI::error_to_string( $result );
				if ( 'up_to_date' !== $result->get_error_code() ) {
					WP_CLI::error( $message );
				} else {
					WP_CLI::success( $message );
				}
			} else {

				$to_version = '';
				if ( file_exists( ABSPATH . 'wp-includes/version.php' ) ) {
					$wp_details = self::get_wp_details();
					$to_version = $wp_details['wp_version'];
				}

				$locale = (string) Utils\get_flag_value( $assoc_args, 'locale', get_locale() );
				$this->cleanup_extra_files( $from_version, $to_version, $locale, $insecure );

				WP_CLI::success( 'WordPress updated successfully.' );
			}
		} else {
			WP_CLI::success( 'WordPress is up to date.' );
		}
	}

	/**
	 * Runs the WordPress database update procedure.
	 *
	 * [--network]
	 * : Update databases for all sites on a network
	 *
	 * [--dry-run]
	 * : Compare database versions without performing the update.
	 *
	 * ## EXAMPLES
	 *
	 *     # Update the WordPress database
	 *     $ wp core update-db
	 *     Success: WordPress database upgraded successfully from db version 36686 to 35700.
	 *
	 *     # Update databases for all sites on a network
	 *     $ wp core update-db --network
	 *     WordPress database upgraded successfully from db version 35700 to 29630 on example.com/
	 *     Success: WordPress database upgraded on 123/123 sites
	 *
	 * @subcommand update-db
	 */
	public function update_db( $args, $assoc_args ) {
		global $wpdb, $wp_db_version, $wp_current_db_version;

		$network = Utils\get_flag_value( $assoc_args, 'network' );
		if ( $network && ! is_multisite() ) {
			WP_CLI::error( 'This is not a multisite installation.' );
		}

		$dry_run = Utils\get_flag_value( $assoc_args, 'dry-run' );
		if ( $dry_run ) {
			WP_CLI::log( 'Performing a dry run, with no database modification.' );
		}

		if ( $network ) {
			$iterator_args = [
				'table' => $wpdb->blogs,
				'where' => [
					'spam'     => 0,
					'deleted'  => 0,
					'archived' => 0,
				],
			];
			$it            = new TableIterator( $iterator_args );
			$success       = 0;
			$total         = 0;
			$site_ids      = [];
			foreach ( $it as $blog ) {
				$total++;
				$site_ids[] = $blog->site_id;
				$url        = $blog->domain . $blog->path;
				$cmd        = "--url={$url} core update-db";
				if ( $dry_run ) {
					$cmd .= ' --dry-run';
				}
				$process = WP_CLI::runcommand(
					$cmd,
					[
						'return'     => 'all',
						'exit_error' => false,
					]
				);
				if ( 0 === (int) $process->return_code ) {
					// See if we can parse the stdout
					if ( preg_match( '#Success: (.+)#', $process->stdout, $matches ) ) {
						$message = rtrim( $matches[1], '.' );
						$message = "{$message} on {$url}";
					} else {
						$message = "Database upgraded successfully on {$url}";
					}
					WP_CLI::log( $message );
					$success++;
				} else {
					WP_CLI::warning( "Database failed to upgrade on {$url}" );
				}
			}
			if ( ! $dry_run && $total && $success === $total ) {
				foreach ( array_unique( $site_ids ) as $site_id ) {
					update_metadata( 'site', $site_id, 'wpmu_upgrade_site', $wp_db_version );
				}
			}
			WP_CLI::success( "WordPress database upgraded on {$success}/{$total} sites." );
		} else {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Replacing WP Core behavior is the goal here.
			$wp_current_db_version = (int) __get_option( 'db_version' );
			if ( $wp_db_version !== $wp_current_db_version ) {
				if ( $dry_run ) {
					WP_CLI::success( "WordPress database will be upgraded from db version {$wp_current_db_version} to {$wp_db_version}." );
				} else {
					// WP upgrade isn't too fussy about generating MySQL warnings such as "Duplicate key name" during an upgrade so suppress.
					$wpdb->suppress_errors();

					// WP upgrade expects `$_SERVER['HTTP_HOST']` to be set in `wp_guess_url()`, otherwise get PHP notice.
					if ( ! isset( $_SERVER['HTTP_HOST'] ) ) {
						$_SERVER['HTTP_HOST'] = 'http://example.com';
					}

					wp_upgrade();

					WP_CLI::success( "WordPress database upgraded successfully from db version {$wp_current_db_version} to {$wp_db_version}." );
				}
			} else {
				WP_CLI::success( "WordPress database already at latest db version {$wp_db_version}." );
			}
		}
	}

	/**
	 * Gets download url based on version, locale and desired file type.
	 *
	 * @param $version
	 * @param string $locale
	 * @param string $file_type
	 * @return string
	 */
	private function get_download_url( $version, $locale = 'en_US', $file_type = 'zip' ) {

		if ( 'nightly' === $version ) {
			if ( 'zip' === $file_type ) {
				return 'https://wordpress.org/nightly-builds/wordpress-latest.zip';
			} else {
				WP_CLI::error( 'Nightly builds are only available in .zip format.' );
			}
		}

		$locale_subdomain = 'en_US' === $locale ? '' : substr( $locale, 0, 2 ) . '.';
		$locale_suffix    = 'en_US' === $locale ? '' : "-{$locale}";

		return "https://{$locale_subdomain}wordpress.org/wordpress-{$version}{$locale_suffix}.{$file_type}";
	}

	/**
	 * Returns update information.
	 */
	private function get_updates( $assoc_args ) {
		wp_version_check();
		$from_api = get_site_transient( 'update_core' );
		if ( ! $from_api ) {
			return [];
		}

		$compare_version = str_replace( '-src', '', $GLOBALS['wp_version'] );

		$updates = [
			'major' => false,
			'minor' => false,
		];
		foreach ( $from_api->updates as $offer ) {

			$update_type = Utils\get_named_sem_ver( $offer->version, $compare_version );
			if ( ! $update_type ) {
				continue;
			}

			// WordPress follow its own versioning which is roughly equivalent to semver
			if ( 'minor' === $update_type ) {
				$update_type = 'major';
			} elseif ( 'patch' === $update_type ) {
				$update_type = 'minor';
			}

			if ( ! empty( $updates[ $update_type ] ) && ! Comparator::greaterThan( $offer->version, $updates[ $update_type ]['version'] ) ) {
				continue;
			}

			$updates[ $update_type ] = [
				'version'     => $offer->version,
				'update_type' => $update_type,
				'package_url' => ! empty( $offer->packages->partial ) ? $offer->packages->partial : $offer->packages->full,
			];
		}

		foreach ( $updates as $type => $value ) {
			if ( empty( $value ) ) {
				unset( $updates[ $type ] );
			}
		}

		foreach ( [ 'major', 'minor' ] as $type ) {
			if ( true === Utils\get_flag_value( $assoc_args, $type ) ) {
				return ! empty( $updates[ $type ] )
					? [ $updates[ $type ] ]
					: false;
			}
		}
		return array_values( $updates );
	}

	/**
	 * Clean up extra files.
	 *
	 * @param string $version_from Starting version that the installation was updated from.
	 * @param string $version_to   Target version that the installation is updated to.
	 * @param string $locale       Locale of the installation.
	 * @param bool   $insecure     Whether to retry without certificate validation on TLS handshake failure.
	 */
	private function cleanup_extra_files( $version_from, $version_to, $locale, $insecure ) {
		if ( ! $version_from || ! $version_to ) {
			WP_CLI::warning( 'Failed to find WordPress version. Please cleanup files manually.' );
			return;
		}

		$old_checksums = self::get_core_checksums( $version_from, $locale ?: 'en_US', $insecure );
		if ( ! is_array( $old_checksums ) ) {
			WP_CLI::warning( "{$old_checksums} Please cleanup files manually." );
			return;
		}
		$new_checksums = self::get_core_checksums( $version_to, $locale ?: 'en_US', $insecure );
		if ( ! is_array( $new_checksums ) ) {
			WP_CLI::warning( "{$new_checksums} Please cleanup files manually." );
			return;
		}

		// Compare the files from the old version and the new version in a case-insensitive manner,
		// to prevent files being incorrectly deleted on systems with case-insensitive filesystems
		// when core changes the case of filenames.
		// The main logic for this was taken from the Joomla project and adapted for WP.
		// See: https://github.com/joomla/joomla-cms/blob/bb5368c7ef9c20270e6e9fcc4b364cd0849082a5/administrator/components/com_admin/script.php#L8158

		$old_filepaths = array_keys( $old_checksums );
		$new_filepaths = array_keys( $new_checksums );

		$new_filepaths = array_combine( array_map( 'strtolower', $new_filepaths ), $new_filepaths );

		$old_filepaths_to_check = array_diff( $old_filepaths, $new_filepaths );

		foreach ( $old_filepaths_to_check as $old_filepath_to_check ) {
			$old_realpath = realpath( ABSPATH . $old_filepath_to_check );

			// On Unix without incorrectly cased file.
			if ( false === $old_realpath ) {
				continue;
			}

			$lowercase_old_filepath_to_check = strtolower( $old_filepath_to_check );

			if ( ! array_key_exists( $lowercase_old_filepath_to_check, $new_filepaths ) ) {
				$files_to_remove[] = $old_filepath_to_check;
				continue;
			}

			// We are now left with only the files that are similar from old to new except for their case.

			$old_basename      = basename( $old_realpath );
			$new_filepath      = $new_filepaths[ $lowercase_old_filepath_to_check ];
			$expected_basename = basename( $new_filepath );
			$new_realpath      = realpath( ABSPATH . $new_filepath );
			$new_basename      = basename( $new_realpath );

			// On Windows or Unix with only the incorrectly cased file.
			if ( $new_basename !== $expected_basename ) {
				WP_CLI::debug( "Renaming file '{$old_filepath_to_check}' => '{$new_filepath}'", 'core' );

				rename( ABSPATH . $old_filepath_to_check, ABSPATH . $old_filepath_to_check . '.tmp' );
				rename( ABSPATH . $old_filepath_to_check . '.tmp', ABSPATH . $new_filepath );

				continue;
			}

			// There might still be an incorrectly cased file on other OS than Windows.
			if ( basename( $old_filepath_to_check ) === $old_basename ) {
				// Check if case-insensitive file system, eg on OSX.
				if ( fileinode( $old_realpath ) === fileinode( $new_realpath ) ) {
					// Check deeper because even realpath or glob might not return the actual case.
					if ( ! in_array( $expected_basename, scandir( dirname( $new_realpath ) ), true ) ) {
						WP_CLI::debug( "Renaming file '{$old_filepath_to_check}' => '{$new_filepath}'", 'core' );

						rename( ABSPATH . $old_filepath_to_check, ABSPATH . $old_filepath_to_check . '.tmp' );
						rename( ABSPATH . $old_filepath_to_check . '.tmp', ABSPATH . $new_filepath );
					}
				} else {
					// On Unix with both files: Delete the incorrectly cased file.
					$files_to_remove[] = $old_filepath_to_check;
				}
			}
		}

		if ( ! empty( $files_to_remove ) ) {
			WP_CLI::log( 'Cleaning up files...' );

			$count = 0;
			foreach ( $files_to_remove as $file ) {

				// wp-content should be considered user data
				if ( 0 === stripos( $file, 'wp-content' ) ) {
					continue;
				}

				if ( file_exists( ABSPATH . $file ) ) {
					unlink( ABSPATH . $file );
					WP_CLI::log( "File removed: {$file}" );
					$count++;
				}
			}

			if ( $count ) {
				WP_CLI::log( number_format( $count ) . ' files cleaned up.' );
			} else {
				WP_CLI::log( 'No files found that need cleaning up.' );
			}
		}
	}

	private static function strip_content_dir( $zip_file ) {
		$new_zip_file = Utils\get_temp_dir() . uniqid( 'wp_' ) . '.zip';
		register_shutdown_function(
			function () use ( $new_zip_file ) {
				if ( file_exists( $new_zip_file ) ) {
					unlink( $new_zip_file );
				}
			}
		);
		// Duplicate file to avoid modifying the original, which could be cache.
		if ( ! copy( $zip_file, $new_zip_file ) ) {
			WP_CLI::error( 'Failed to copy ZIP file.' );
		}
		$zip = new ZipArchive();
		$res = $zip->open( $new_zip_file );
		if ( true === $res ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			for ( $i = 0; $i < $zip->numFiles; $i++ ) {
				$info = $zip->statIndex( $i );
				if ( false !== stripos( $info['name'], 'wp-content/' ) ) {
					$zip->deleteIndex( $i );
				}
			}
			$zip->close();
			return $new_zip_file;
		} else {
			WP_CLI::error( 'ZipArchive failed to open ZIP file.' );
		}
	}

}
