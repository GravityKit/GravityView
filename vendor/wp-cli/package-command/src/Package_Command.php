<?php

use Composer\Composer;
use Composer\Config;
use Composer\Config\JsonConfigSource;
use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\Request;
use Composer\EventDispatcher\Event;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Installer;
use Composer\Json\JsonFile;
use Composer\Package;
use Composer\Package\BasePackage;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionParser;
use Composer\Package\Version\VersionSelector;
use Composer\Repository;
use Composer\Repository\CompositeRepository;
use Composer\Repository\ComposerRepository;
use Composer\Repository\RepositoryManager;
use Composer\Util\Filesystem;
use Composer\Util\HttpDownloader;
use WP_CLI\ComposerIO;
use WP_CLI\Extractor;
use WP_CLI\Utils;
use WP_CLI\JsonManipulator;
use WP_CLI\PackageManagerEventSubscriber;

/**
 * Lists, installs, and removes WP-CLI packages.
 *
 * WP-CLI packages are community-maintained projects built on WP-CLI. They can
 * contain WP-CLI commands, but they can also just extend WP-CLI in some way.
 *
 * Learn how to create your own command from the
 * [Commands Cookbook](https://make.wordpress.org/cli/handbook/commands-cookbook/)
 *
 * ## EXAMPLES
 *
 *     # List installed packages
 *     $ wp package list
 *     +-----------------------+------------------------------------------+---------+----------+
 *     | name                  | description                              | authors | version  |
 *     +-----------------------+------------------------------------------+---------+----------+
 *     | wp-cli/server-command | Start a development server for WordPress |         | dev-main |
 *     +-----------------------+------------------------------------------+---------+----------+
 *
 *     # Install the latest development version of the package
 *     $ wp package install wp-cli/server-command
 *     Installing package wp-cli/server-command (dev-main)
 *     Updating /home/person/.wp-cli/packages/composer.json to require the package...
 *     Using Composer to install the package...
 *     ---
 *     Loading composer repositories with package information
 *     Updating dependencies
 *     Resolving dependencies through SAT
 *     Dependency resolution completed in 0.005 seconds
 *     Analyzed 732 packages to resolve dependencies
 *     Analyzed 1034 rules to resolve dependencies
 *      - Installing package
 *     Writing lock file
 *     Generating autoload files
 *     ---
 *     Success: Package installed.
 *
 *     # Uninstall package
 *     $ wp package uninstall wp-cli/server-command
 *     Removing require statement from /home/person/.wp-cli/packages/composer.json
 *     Deleting package directory /home/person/.wp-cli/packages/vendor/wp-cli/server-command
 *     Regenerating Composer autoload.
 *     Success: Uninstalled package.
 *
 * @package WP-CLI
 *
 * @when before_wp_load
 */
class Package_Command extends WP_CLI_Command {

	const PACKAGE_INDEX_URL = 'https://wp-cli.org/package-index/';
	const SSL_CERTIFICATE   = '/rmccue/requests/library/Requests/Transport/cacert.pem';

	const DEFAULT_DEV_BRANCH_CONSTRAINTS = 'dev-main || dev-master || dev-trunk';

	private $version_selector = false;

	/**
	 * Default author data used while creating default WP-CLI packages composer.json.
	 *
	 * @var array
	 */
	private $author_data = [
		'name'  => 'WP-CLI',
		'email' => 'noreply@wpcli.org',
	];

	/**
	 * Default repository data used while creating default WP-CLI packages composer.json.
	 * @var array
	 */
	private $composer_type_package = [
		'type' => 'composer',
		'url'  => self::PACKAGE_INDEX_URL,
	];

	/**
	 * Browses WP-CLI packages available for installation.
	 *
	 * Lists packages available for installation from the [Package Index](http://wp-cli.org/package-index/).
	 * Although the package index will remain in place for backward compatibility reasons, it has been
	 * deprecated and will not be updated further. Please refer to https://github.com/wp-cli/ideas/issues/51
	 * to read about its potential replacement.
	 *
	 * ## OPTIONS
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
	 *   - ids
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each package:
	 *
	 * * name
	 * * description
	 * * authors
	 * * version
	 *
	 * There are no optionally available fields.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp package browse --format=yaml
	 *     ---
	 *     10up/mu-migration:
	 *       name: 10up/mu-migration
	 *       description: A set of WP-CLI commands to support the migration of single WordPress instances to multisite
	 *       authors: Nícholas André
	 *       version: dev-main, dev-develop
	 *     aaemnnosttv/wp-cli-dotenv-command:
	 *       name: aaemnnosttv/wp-cli-dotenv-command
	 *       description: Dotenv commands for WP-CLI
	 *       authors: Evan Mattson
	 *       version: v0.1, v0.1-beta.1, v0.2, dev-main, dev-dev, dev-develop, dev-tests/behat
	 *     aaemnnosttv/wp-cli-http-command:
	 *       name: aaemnnosttv/wp-cli-http-command
	 *       description: WP-CLI command for using the WordPress HTTP API
	 *       authors: Evan Mattson
	 *       version: dev-main
	 */
	public function browse( $_, $assoc_args ) {
		$this->set_composer_auth_env_var();
		if ( empty( $assoc_args['format'] ) || 'table' === $assoc_args['format'] ) {
			WP_CLI::line( WP_CLI::colorize( '%CAlthough the package index will remain in place for backward compatibility reasons, it has been deprecated and will not be updated further. Please refer to https://github.com/wp-cli/ideas/issues/51 to read about its potential replacement.%n' ) );
		}
		$this->show_packages( 'browse', $this->get_community_packages(), $assoc_args );
	}

	/**
	 * Installs a WP-CLI package.
	 *
	 * Packages are required to be a valid Composer package, and can be
	 * specified as:
	 *
	 * * Package name from WP-CLI's package index.
	 * * Git URL accessible by the current shell user.
	 * * Path to a directory on the local machine.
	 * * Local or remote .zip file.
	 *
	 * Packages are installed to `~/.wp-cli/packages/` by default. Use the
	 * `WP_CLI_PACKAGES_DIR` environment variable to provide a custom path.
	 *
	 * When installing a local directory, WP-CLI simply registers a
	 * reference to the directory. If you move or delete the directory, WP-CLI's
	 * reference breaks.
	 *
	 * When installing a .zip file, WP-CLI extracts the package to
	 * `~/.wp-cli/packages/local/<package-name>`.
	 *
	 * ## OPTIONS
	 *
	 * <name|git|path|zip>
	 * : Name, git URL, directory path, or .zip file for the package to install.
	 * Names can optionally include a version constraint
	 * (e.g. wp-cli/server-command:@stable).
	 *
	 * [--insecure]
	 * : Retry downloads without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.
	 *
	 * ## EXAMPLES
	 *
	 *     # Install a package hosted at a git URL.
	 *     $ wp package install runcommand/hook
	 *
	 *     # Install the latest stable version.
	 *     $ wp package install wp-cli/server-command:@stable
	 *
	 *     # Install a package hosted at a GitLab.com URL.
	 *     $ wp package install https://gitlab.com/foo/wp-cli-bar-command.git
	 *
	 *     # Install a package in a .zip file.
	 *     $ wp package install google-sitemap-generator-cli.zip
	 */
	public function install( $args, $assoc_args ) {
		list( $package_name ) = $args;

		$insecure = (bool) Utils\get_flag_value( $assoc_args, 'insecure', false );

		$this->set_composer_auth_env_var();
		$git_package = false;
		$dir_package = false;
		$version     = '';
		if ( $this->is_git_repository( $package_name ) ) {
			if ( '' === $version ) {
				$version = "dev-{$this->get_github_default_branch( $package_name, $insecure )}";
			}
			$git_package = $package_name;
			$matches     = [];
			if ( preg_match( '#([^:\/]+\/[^\/]+)\.git#', $package_name, $matches ) ) {
				$package_name = $this->check_git_package_name( $matches[1], $package_name, $version, $insecure );
			} else {
				WP_CLI::error( "Couldn't parse package name from expected path '<name>/<package>'." );
			}
		} elseif ( ( false !== strpos( $package_name, '://' ) && false !== stripos( $package_name, '.zip' ) )
			|| ( pathinfo( $package_name, PATHINFO_EXTENSION ) === 'zip' && is_file( $package_name ) ) ) {
			// Download the remote ZIP file to a temp directory
			$temp = false;
			if ( false !== strpos( $package_name, '://' ) ) {
				$temp     = Utils\get_temp_dir() . uniqid( 'wp-cli-package_', true /*more_entropy*/ ) . '.zip';
				$options  = [
					'timeout'  => 600,
					'filename' => $temp,
					'insecure' => $insecure,
				];
				$response = Utils\http_request( 'GET', $package_name, null, [], $options );
				if ( 20 !== (int) substr( $response->status_code, 0, 2 ) ) {
					@unlink( $temp ); // @codingStandardsIgnoreLine
					WP_CLI::error( sprintf( "Couldn't download package from '%s' (HTTP code %d).", $package_name, $response->status_code ) );
				}
				$package_name = $temp;
			}
			$dir_package = Utils\get_temp_dir() . uniqid( 'wp-cli-package_', true /*more_entropy*/ );
			try {
				// Extract the package to get the package name
				Extractor::extract( $package_name, $dir_package );
				if ( $temp ) {
					unlink( $temp );
					$temp = false;
				}
				list( $package_name, $version ) = self::get_package_name_and_version_from_dir_package( $dir_package );
				// Move to a location based on the package name
				$local_dir          = rtrim( WP_CLI::get_runner()->get_packages_dir_path(), '/' ) . '/local/';
				$actual_dir_package = $local_dir . str_replace( '/', '-', $package_name );
				Extractor::copy_overwrite_files( $dir_package, $actual_dir_package );
				Extractor::rmdir( $dir_package );
				// Behold, the extracted package
				$dir_package = $actual_dir_package;
			} catch ( Exception $e ) {
				if ( $temp ) {
					unlink( $temp );
				}
				if ( file_exists( $dir_package ) ) {
					try {
						Extractor::rmdir( $dir_package );
					} catch ( Exception $rmdir_e ) {
						WP_CLI::warning( $rmdir_e->getMessage() );
					}
				}
				WP_CLI::error( $e->getMessage() );
			}
		} elseif ( is_dir( $package_name ) && file_exists( $package_name . '/composer.json' ) ) {
			$dir_package = $package_name;
			if ( ! Utils\is_path_absolute( $dir_package ) ) {
				$dir_package = getcwd() . DIRECTORY_SEPARATOR . $dir_package;
			}
			list( $package_name, $version ) = self::get_package_name_and_version_from_dir_package( $dir_package );
		} else {
			if ( false !== strpos( $package_name, ':' ) ) {
				list( $package_name, $version ) = explode( ':', $package_name );
			}
			$package = $this->get_package_by_shortened_identifier( $package_name );
			if ( ! $package ) {
				WP_CLI::error( sprintf( "Invalid package: shortened identifier '%s' not found.", $package_name ) );
			}
			if ( is_string( $package ) ) {
				if ( $this->is_git_repository( $package ) ) {
					$git_package = $package;

					if ( '' === $version ) {
						$version = "dev-{$this->get_github_default_branch( $package_name, $insecure )}";
					}

					if ( '@stable' === $version ) {
						$tag     = $this->get_github_latest_release_tag( $package_name, $insecure );
						$version = $this->guess_version_constraint_from_tag( $tag );
					}
					$package_name = $this->check_github_package_name( $package_name, $version, $insecure );
				}
			} elseif ( $package_name !== $package->getPrettyName() ) {
				// BC support for specifying lowercase names for mixed-case package index packages - don't bother warning.
				$package_name = $package->getPrettyName();
			}
		}

		if ( $this->is_composer_v2() ) {
			$package_name = function_exists( 'mb_strtolower' )
				? mb_strtolower( $package_name )
				: strtolower( $package_name );
		}

		if ( '' === $version ) {
			$version = self::DEFAULT_DEV_BRANCH_CONSTRAINTS;
		}

		WP_CLI::log( sprintf( 'Installing package %s (%s)', $package_name, $version ) );

		// Read the WP-CLI packages composer.json and do some initial error checking.
		list( $json_path, $composer_backup, $composer_backup_decoded ) = $this->get_composer_json_path_backup_decoded();

		// Revert on shutdown if `$revert` is true (set to false on success).
		$revert = true;
		$this->register_revert_shutdown_function( $json_path, $composer_backup, $revert );

		// Add the 'require' to composer.json
		WP_CLI::log( sprintf( 'Updating %s to require the package...', $json_path ) );
		$json_manipulator = new JsonManipulator( $composer_backup );
		$json_manipulator->addMainKey( 'name', 'wp-cli/wp-cli' );
		$json_manipulator->addMainKey( 'version', self::get_wp_cli_version_composer() );
		$json_manipulator->addLink( 'require', $package_name, $version, false /*sortPackages*/, true /*caseInsensitive*/ );
		$json_manipulator->addConfigSetting( 'secure-http', true );

		$package_args = [];
		if ( $git_package ) {
			WP_CLI::log( sprintf( 'Registering %s as a VCS repository...', $git_package ) );
			$package_args = [
				'type' => 'vcs',
				'url'  => $git_package,
			];
			$json_manipulator->addSubNode(
				'repositories',
				$package_name,
				$package_args,
				true /*caseInsensitive*/
			);
		} elseif ( $dir_package ) {
			WP_CLI::log( sprintf( 'Registering %s as a path repository...', $dir_package ) );
			$package_args = [
				'type' => 'path',
				'url'  => $dir_package,
			];
			$json_manipulator->addSubNode(
				'repositories',
				$package_name,
				$package_args,
				true /*caseInsensitive*/
			);
		}
		// If the composer file does not contain the current package index repository, refresh the repository definition.
		if ( empty( $composer_backup_decoded['repositories']['wp-cli']['url'] ) || self::PACKAGE_INDEX_URL !== $composer_backup_decoded['repositories']['wp-cli']['url'] ) {
			WP_CLI::log( 'Updating package index repository url...' );
			$package_args = $this->composer_type_package;
			$json_manipulator->addRepository(
				'wp-cli',
				$package_args
			);
		}

		file_put_contents( $json_path, $json_manipulator->getContents() );
		$composer = $this->get_composer();

		// Set up the EventSubscriber
		$event_subscriber = new PackageManagerEventSubscriber();
		$composer->getEventDispatcher()->addSubscriber( $event_subscriber );
		// Set up the installer
		$install = Installer::create( new ComposerIO(), $composer );
		$install->setUpdate( true ); // Installer class will only override composer.lock with this flag
		$install->setPreferSource( true ); // Use VCS when VCS for easier contributions.

		// Try running the installer, but revert composer.json if failed
		WP_CLI::log( 'Using Composer to install the package...' );
		WP_CLI::log( '---' );
		$res = false;
		try {
			$res = $install->run();
		} catch ( Exception $e ) {
			WP_CLI::warning( $e->getMessage() );
		}

		// TODO: The --insecure flag should cause another Composer run with verify disabled.

		WP_CLI::log( '---' );

		if ( 0 === $res ) {
			$revert = false;
			WP_CLI::success( 'Package installed.' );
		} else {
			$res_msg = $res ? " (Composer return code {$res})" : ''; // $res may be null apparently.
			WP_CLI::debug( "composer.json content:\n" . file_get_contents( $json_path ), 'packages' );
			WP_CLI::error( "Package installation failed{$res_msg}." );
		}
	}

	/**
	 * Lists installed WP-CLI packages.
	 *
	 * ## OPTIONS
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
	 *   - ids
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each package:
	 *
	 * * name
	 * * authors
	 * * version
	 * * update
	 * * update_version
	 *
	 * These fields are optionally available:
	 *
	 * * description
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp package list
	 *     +-----------------------+------------------------------------------+---------+----------+
	 *     | name                  | description                              | authors | version  |
	 *     +-----------------------+------------------------------------------+---------+----------+
	 *     | wp-cli/server-command | Start a development server for WordPress |         | dev-main |
	 *     +-----------------------+------------------------------------------+---------+----------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$this->set_composer_auth_env_var();
		$this->show_packages( 'list', $this->get_installed_packages(), $assoc_args );
	}

	/**
	 * Gets the path to an installed WP-CLI package, or the package directory.
	 *
	 * If you want to contribute to a package, this is a great way to jump to it.
	 *
	 * ## OPTIONS
	 *
	 * [<name>]
	 * : Name of the package to get the directory for.
	 *
	 * ## EXAMPLES
	 *
	 *     # Get package path
	 *     $ wp package path
	 *     /home/person/.wp-cli/packages/
	 *
	 *     # Change directory to package path
	 *     $ cd $(wp package path) && pwd
	 *     /home/vagrant/.wp-cli/packages
	 */
	public function path( $args ) {
		$packages_dir = WP_CLI::get_runner()->get_packages_dir_path();
		if ( ! empty( $args ) ) {
			$packages_dir .= 'vendor/' . $args[0];
			if ( ! is_dir( $packages_dir ) ) {
				WP_CLI::error( 'Invalid package name.' );
			}
		}
		WP_CLI::line( $packages_dir );
	}

	/**
	 * Updates all installed WP-CLI packages to their latest version.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp package update
	 *     Using Composer to update packages...
	 *     ---
	 *     Loading composer repositories with package information
	 *     Updating dependencies
	 *     Resolving dependencies through SAT
	 *     Dependency resolution completed in 0.074 seconds
	 *     Analyzed 1062 packages to resolve dependencies
	 *     Analyzed 22383 rules to resolve dependencies
	 *     Writing lock file
	 *     Generating autoload files
	 *     ---
	 *     Success: Packages updated.
	 */
	public function update() {
		$this->set_composer_auth_env_var();
		$composer = $this->get_composer();

		// Set up the EventSubscriber
		$event_subscriber = new PackageManagerEventSubscriber();
		$composer->getEventDispatcher()->addSubscriber( $event_subscriber );

		// Set up the installer
		$install = Installer::create( new ComposerIO(), $composer );
		$install->setUpdate( true ); // Installer class will only override composer.lock with this flag
		$install->setPreferSource( true ); // Use VCS when VCS for easier contributions.
		WP_CLI::log( 'Using Composer to update packages...' );
		WP_CLI::log( '---' );
		$res = false;
		try {
			$res = $install->run();
		} catch ( Exception $e ) {
			WP_CLI::warning( $e->getMessage() );
		}
		WP_CLI::log( '---' );

		// TODO: The --insecure (to be added here) flag should cause another Composer run with verify disabled.

		if ( 0 === $res ) {
			WP_CLI::success( 'Packages updated.' );
		} else {
			$res_msg = $res ? " (Composer return code {$res})" : ''; // $res may be null apparently.
			WP_CLI::error( "Failed to update packages{$res_msg}." );
		}
	}

	/**
	 * Uninstalls a WP-CLI package.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Name of the package to uninstall.
	 *
	 * [--insecure]
	 * : Retry downloads without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp package uninstall wp-cli/server-command
	 *     Removing require statement from /home/person/.wp-cli/packages/composer.json
	 *     Deleting package directory /home/person/.wp-cli/packages/vendor/wp-cli/server-command
	 *     Regenerating Composer autoload.
	 *     Success: Uninstalled package.
	 */
	public function uninstall( $args, $assoc_args ) {
		list( $package_name ) = $args;

		$insecure = (bool) Utils\get_flag_value( $assoc_args, 'insecure', false );

		$this->set_composer_auth_env_var();
		$package = $this->get_installed_package_by_name( $package_name );
		if ( false === $package ) {
			$package_name = $this->get_package_by_shortened_identifier( $package_name );
			if ( false === $package_name ) {
				WP_CLI::error( 'Package not installed.' );
			}
			$version = "dev-{$this->get_github_default_branch( $package_name, $insecure )}";
			$matches = [];
			if ( preg_match( '#^(?:https?://github\.com/|git@github\.com:)(?<repo_name>.*?).git$#', $package_name, $matches ) ) {
				$package_name = $this->check_git_package_name( $matches['repo_name'], $package_name, $version, $insecure );
			}
		} else {
			$package_name = $package->getPrettyName(); // Make sure package name is what's in composer.json.
		}

		// Read the WP-CLI packages composer.json and do some initial error checking.
		list( $json_path, $composer_backup, $composer_backup_decoded ) = $this->get_composer_json_path_backup_decoded();

		// Revert on shutdown if `$revert` is true (set to false on success).
		$revert = true;
		$this->register_revert_shutdown_function( $json_path, $composer_backup, $revert );

		// Remove the 'require' from composer.json.
		WP_CLI::log( sprintf( 'Removing require statement for package \'%s\' from %s', $package_name, $json_path ) );
		$manipulator = new JsonManipulator( $composer_backup );
		$manipulator->removeSubNode( 'require', $package_name, true /*caseInsensitive*/ );

		// Remove the 'repository' details from composer.json.
		WP_CLI::log( sprintf( 'Removing repository details from %s', $json_path ) );
		$manipulator->removeSubNode( 'repositories', $package_name, true /*caseInsensitive*/ );

		file_put_contents( $json_path, $manipulator->getContents() );
		$composer = $this->get_composer();

		// Set up the installer.
		$install = Installer::create( new NullIO(), $composer );
		$install->setUpdate( true ); // Installer class will only override composer.lock with this flag
		$install->setPreferSource( true ); // Use VCS when VCS for easier contributions.

		WP_CLI::log( 'Removing package directories and regenerating autoloader...' );
		$res = false;
		try {
			$res = $install->run();
		} catch ( Exception $e ) {
			WP_CLI::warning( $e->getMessage() );
		}

		if ( 0 === $res ) {
			$revert = false;
			WP_CLI::success( 'Uninstalled package.' );
		} else {
			$res_msg = $res ? " (Composer return code {$res})" : ''; // $res may be null apparently.
			WP_CLI::error( "Package removal failed{$res_msg}." );
		}
	}

	/**
	 * Checks whether a package is a WP-CLI community package based
	 * on membership in our package index.
	 *
	 * @param object      $package     A package object
	 * @return bool
	 */
	private function is_community_package( $package ) {
		return $this->package_index()->hasPackage( $package );
	}

	/**
	 * Gets a Composer instance.
	 */
	private function get_composer() {
		$this->avoid_composer_ca_bundle();
		try {
			$composer_path = $this->get_composer_json_path();

			// Composer's auto-load generating code makes some assumptions about where
			// the 'vendor-dir' is, and where Composer is running from.
			// Best to just pretend we're installing a package from ~/.wp-cli or similar
			chdir( pathinfo( $composer_path, PATHINFO_DIRNAME ) );

			// Prevent DateTime error/warning when no timezone set.
			// Note: The package is loaded before WordPress load, For environments that don't have set time in php.ini.
			// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set,WordPress.PHP.NoSilencedErrors.Discouraged
			date_default_timezone_set( @date_default_timezone_get() );

			$composer = Factory::create( new NullIO(), $composer_path );
		} catch ( Exception $e ) {
			WP_CLI::error( sprintf( 'Failed to get composer instance: %s', $e->getMessage() ) );
		}
		return $composer;
	}

	/**
	 * Gets all of the community packages.
	 *
	 * @return array
	 */
	private function get_community_packages() {
		static $community_packages;

		if ( null === $community_packages ) {
			$this->avoid_composer_ca_bundle();
			try {
				$community_packages = $this->package_index()->getPackages();
			} catch ( Exception $e ) {
				WP_CLI::error( $e->getMessage() );
			}
		}

		return $community_packages;
	}

	/**
	 * Gets the package index instance
	 *
	 * We need to construct the instance manually, because there's no way to select
	 * a particular instance using $composer->getRepositoryManager()
	 *
	 * @return ComposerRepository
	 */
	private function package_index() {
		static $package_index;

		if ( ! $package_index ) {
			$config_args = [
				'config' => [
					'secure-http' => true,
					'home'        => dirname( $this->get_composer_json_path() ),
				],
			];
			$config      = new Config();
			$config->merge( $config_args );
			$config->setConfigSource( new JsonConfigSource( $this->get_composer_json() ) );

			$io = new NullIO();
			try {
				if ( $this->is_composer_v2() ) {
					$http_downloader = new HttpDownloader( $io, $config );
					$package_index   = new ComposerRepository( [ 'url' => self::PACKAGE_INDEX_URL ], $io, $config, $http_downloader );
				} else {
					$package_index = new ComposerRepository( [ 'url' => self::PACKAGE_INDEX_URL ], $io, $config );
				}
			} catch ( Exception $e ) {
				WP_CLI::error( $e->getMessage() );
			}
		}

		return $package_index;
	}

	/**
	 * Displays a set of packages
	 *
	 * @param string $context
	 * @param array
	 * @param array
	 */
	private function show_packages( $context, $packages, $assoc_args ) {
		if ( 'list' === $context ) {
			$default_fields = [
				'name',
				'authors',
				'version',
				'update',
				'update_version',
			];
		} elseif ( 'browse' === $context ) {
			$default_fields = [
				'name',
				'description',
				'authors',
				'version',
			];
		}
		$defaults   = [
			'fields' => implode( ',', $default_fields ),
			'format' => 'table',
		];
		$assoc_args = array_merge( $defaults, $assoc_args );

		$composer = $this->get_composer();
		$list     = [];
		foreach ( $packages as $package ) {
			$name = $package->getPrettyName();
			if ( isset( $list[ $name ] ) ) {
				$list[ $name ]['version'][] = $package->getPrettyVersion();
			} else {
				$package_output                = [];
				$package_output['name']        = $package->getPrettyName();
				$package_output['description'] = $package->getDescription();
				$package_output['authors']     = implode( ', ', array_column( (array) $package->getAuthors(), 'name' ) );
				$package_output['version']     = [ $package->getPrettyVersion() ];
				$update                        = 'none';
				$update_version                = '';
				if ( 'list' === $context ) {
					try {
						$latest = $this->find_latest_package( $package, $composer, null );
						if ( $latest && $latest->getFullPrettyVersion() !== $package->getFullPrettyVersion() ) {
							$update         = 'available';
							$update_version = $latest->getPrettyVersion();
						}
					} catch ( Exception $e ) {
						WP_CLI::warning( $e->getMessage() );
						$update         = 'error';
						$update_version = $update;
					}
				}
				$package_output['update']         = $update;
				$package_output['update_version'] = $update_version;
				$package_output['pretty_name']    = $package->getPrettyName(); // Deprecated but kept for BC with package-command 1.0.8.
				$list[ $package_output['name'] ]  = $package_output;
			}
		}

		$list = array_map(
			function( $package ) {
				$package['version'] = implode( ', ', $package['version'] );
				return $package;
			},
			$list
		);

		ksort( $list );
		if ( 'ids' === $assoc_args['format'] ) {
			$list = array_keys( $list );
		}
		Utils\format_items( $assoc_args['format'], $list, $assoc_args['fields'] );
	}

	/**
	 * Gets a package by its shortened identifier.
	 *
	 * A shortened identifier has the form `<vendor>/<package>`.
	 *
	 * This method first checks the deprecated package index, for BC reasons,
	 * and then falls back to the corresponding GitHub URL.
	 *
	 * @param string $package_name Name of the package to get.
	 * @param bool   $insecure     Optional. Whether to insecurely retry downloads that failed TLS handshake. Defaults
	 *                             to false.
	 */
	private function get_package_by_shortened_identifier( $package_name, $insecure = false ) {
		// Check the package index first, so we don't break existing behavior.
		$lc_package_name = strtolower( $package_name ); // For BC check.
		foreach ( $this->get_community_packages() as $package ) {
			if ( $package_name === $package->getPrettyName() ) {
				return $package;
			}
			// For BC allow getting by lowercase name.
			if ( $lc_package_name === $package->getName() ) {
				return $package;
			}
		}

		$options = [ 'insecure' => $insecure ];

		// Check if the package exists on Packagist.
		$url      = "https://repo.packagist.org/p/{$package_name}.json";
		$response = Utils\http_request( 'GET', $url, null, [], $options );
		if ( 20 === (int) substr( $response->status_code, 0, 2 ) ) {
			return $package_name;
		}

		// Fall back to GitHub URL if we had no match yet.
		$url          = "https://github.com/{$package_name}.git";
		$github_token = getenv( 'GITHUB_TOKEN' ); // Use GITHUB_TOKEN if available to avoid authorization failures or rate-limiting.
		$headers      = $github_token ? [ 'Authorization' => 'token ' . $github_token ] : [];
		$response     = Utils\http_request( 'GET', $url, null /*data*/, $headers, $options );
		if ( 20 === (int) substr( $response->status_code, 0, 2 ) ) {
			return $url;
		}

		return false;
	}

	/**
	 * Gets the installed community packages.
	 */
	private function get_installed_packages() {
		$composer = $this->get_composer();

		$repo                   = $composer->getRepositoryManager()->getLocalRepository();
		$existing               = json_decode( file_get_contents( $this->get_composer_json_path() ), true );
		$installed_package_keys = ! empty( $existing['require'] ) ? array_keys( $existing['require'] ) : [];
		if ( empty( $installed_package_keys ) ) {
			return [];
		}
		// For use by legacy incorrect name check.
		$lc_installed_package_keys = array_map( 'strtolower', $installed_package_keys );
		$installed_packages        = [];
		foreach ( $repo->getCanonicalPackages() as $package ) {
			$idx = array_search( $package->getName(), $lc_installed_package_keys, true );
			// Use pretty name as it's case sensitive and what's in composer.json (or at least should be).
			if ( in_array( $package->getPrettyName(), $installed_package_keys, true ) ) {
				$installed_packages[] = $package;
			} elseif ( false !== $idx ) { // Legacy incorrect name check.
				if ( ! $this->is_composer_v2() ) {
					WP_CLI::warning( sprintf( "Found package '%s' misnamed '%s' in '%s'.", $package->getPrettyName(), $installed_package_keys[ $idx ], $this->get_composer_json_path() ) );
				}
				$installed_packages[] = $package;
			}
		}
		return $installed_packages;
	}

	/**
	 * Gets an installed package by its name.
	 */
	private function get_installed_package_by_name( $package_name ) {
		foreach ( $this->get_installed_packages() as $package ) {
			if ( $package_name === $package->getPrettyName() ) {
				return $package;
			}
			// Also check non-pretty (lowercase) name in case of legacy incorrect name.
			if ( $package_name === $package->getName() ) {
				return $package;
			}
		}
		return false;
	}

	/**
	 * Checks if the package name provided is already installed.
	 */
	private function is_package_installed( $package_name ) {
		if ( $this->get_installed_package_by_name( $package_name ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Gets the name of the package from the composer.json in a directory path
	 *
	 * @param string $dir_package
	 * @return array Two-element array containing package name and version.
	 */
	private static function get_package_name_and_version_from_dir_package( $dir_package ) {
		$composer_file = $dir_package . '/composer.json';
		if ( ! file_exists( $composer_file ) ) {
			WP_CLI::error( sprintf( "Invalid package: composer.json file '%s' not found.", $composer_file ) );
		}
		$composer_data = json_decode( file_get_contents( $composer_file ), true );
		if ( null === $composer_data ) {
			WP_CLI::error( sprintf( "Invalid package: failed to parse composer.json file '%s' as json.", $composer_file ) );
		}
		if ( empty( $composer_data['name'] ) ) {
			WP_CLI::error( sprintf( "Invalid package: no name in composer.json file '%s'.", $composer_file ) );
		}
		$package_name = $composer_data['name'];
		$version      = self::DEFAULT_DEV_BRANCH_CONSTRAINTS;
		if ( ! empty( $composer_data['version'] ) ) {
			$version = $composer_data['version'];
		}
		return [ $package_name, $version ];
	}

	/**
	 * Gets the WP-CLI packages composer.json object.
	 */
	private function get_composer_json() {
		return new JsonFile( $this->get_composer_json_path() );
	}

	/**
	 * Gets the absolute path to the WP-CLI packages composer.json.
	 */
	private function get_composer_json_path() {
		static $composer_path;

		if ( null === $composer_path || getenv( 'WP_CLI_TEST_PACKAGE_GET_COMPOSER_JSON_PATH' ) ) {

			if ( getenv( 'WP_CLI_PACKAGES_DIR' ) ) {
				$composer_path = Utils\trailingslashit( getenv( 'WP_CLI_PACKAGES_DIR' ) ) . 'composer.json';
			} else {
				$composer_path = Utils\trailingslashit( Utils\get_home_dir() ) . '.wp-cli/packages/composer.json';
			}

			// `composer.json` and its directory might need to be created
			if ( ! file_exists( $composer_path ) ) {
				$composer_path = $this->create_default_composer_json( $composer_path );
			} else {
				$composer_path = realpath( $composer_path );
				if ( false === $composer_path ) {
					$error = error_get_last();
					WP_CLI::error( sprintf( "Composer path '%s' for packages/composer.json not found: %s", $composer_path, $error['message'] ) );
				}
			}
		}

		return $composer_path;
	}

	/**
	 * Gets the WP-CLI version for composer.json
	 */
	private static function get_wp_cli_version_composer() {
		preg_match( '#^[0-9\.]+(-(alpha|beta)[^-]{0,})?#', WP_CLI_VERSION, $matches );
		$version = isset( $matches[0] ) ? $matches[0] : '';
		return $version;
	}

	/**
	 * Creates a default WP-CLI packages composer.json.
	 *
	 * @param string $composer_path Where the composer.json should be created
	 * @return string Returns the absolute path of the newly created default WP-CLI packages composer.json.
	 */
	private function create_default_composer_json( $composer_path ) {

		$composer_dir = pathinfo( $composer_path, PATHINFO_DIRNAME );
		if ( ! is_dir( $composer_dir ) ) {
			if ( ! @mkdir( $composer_dir, 0777, true ) ) { // @codingStandardsIgnoreLine
				$error = error_get_last();
				WP_CLI::error( sprintf( "Composer directory '%s' for packages couldn't be created: %s", $composer_dir, $error['message'] ) );
			}
		}

		$composer_dir = realpath( $composer_dir );
		if ( false === $composer_dir ) {
			$error = error_get_last();
			WP_CLI::error( sprintf( "Composer directory '%s' for packages not found: %s", $composer_dir, $error['message'] ) );
		}

		$composer_path = Utils\trailingslashit( $composer_dir ) . Utils\basename( $composer_path );

		$json_file = new JsonFile( $composer_path );

		$repositories = (object) [
			'wp-cli' => (object) $this->composer_type_package,
		];

		$options = [
			'name'              => 'wp-cli/wp-cli',
			'description'       => 'Installed community packages used by WP-CLI',
			'version'           => self::get_wp_cli_version_composer(),
			'authors'           => [ (object) $this->author_data ],
			'homepage'          => self::PACKAGE_INDEX_URL,
			'require'           => new stdClass(),
			'require-dev'       => new stdClass(),
			'minimum-stability' => 'dev',
			'prefer-stable'     => true,
			'license'           => 'MIT',
			'repositories'      => $repositories,
		];

		try {
			$json_file->write( $options );
		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		return $composer_path;
	}

	/**
	 * Given a package, this finds the latest package matching it
	 *
	 * @param  PackageInterface $package
	 * @param  Composer         $composer
	 * @param  string           $phpVersion
	 * @param  bool             $minorOnly
	 *
	 * @return PackageInterface|null
	 */
	private function find_latest_package( PackageInterface $package, Composer $composer, $php_version, $minor_only = false ) {
		// Find the latest version allowed in this pool/repository set.
		$name             = $package->getPrettyName();
		$version_selector = $this->get_version_selector( $composer );
		$stability        = $composer->getPackage()->getMinimumStability();
		$flags            = $composer->getPackage()->getStabilityFlags();
		if ( isset( $flags[ $name ] ) ) {
			$stability = array_search( $flags[ $name ], BasePackage::$stabilities, true );
		}
		$best_stability = $stability;
		if ( $composer->getPackage()->getPreferStable() ) {
			$best_stability = $package->getStability();
		}
		$target_version = null;
		if ( 0 === strpos( $package->getVersion(), 'dev-' ) ) {
			$target_version = $package->getVersion();
		}
		if ( null === $target_version && $minor_only ) {
			$target_version = '^' . $package->getVersion();
		}

		if ( $this->is_composer_v2() ) {
			return $version_selector->findBestCandidate( $name, $target_version, $best_stability );
		}

		return $version_selector->findBestCandidate( $name, $target_version, $php_version, $best_stability );
	}

	private function get_version_selector( Composer $composer ) {
		if ( ! $this->version_selector ) {
			if ( $this->is_composer_v2() ) {
				$repository_set = new Repository\RepositorySet(
					$composer->getPackage()->getMinimumStability(),
					$composer->getPackage()->getStabilityFlags()
				);
				$repository_set->addRepository( new CompositeRepository( $composer->getRepositoryManager()->getRepositories() ) );
				$this->version_selector = new VersionSelector( $repository_set );
			} else {
				$pool = new Pool( $composer->getPackage()->getMinimumStability(), $composer->getPackage()->getStabilityFlags() );
				$pool->addRepository( new CompositeRepository( $composer->getRepositoryManager()->getRepositories() ) );
				$this->version_selector = new VersionSelector( $pool );
			}
		}

		return $this->version_selector;
	}

	/**
	 * Checks whether a given package is a git repository.
	 *
	 * @param string $package Package name to check.
	 *
	 * @return bool Whether the package is a git repository.
	 */
	private function is_git_repository( $package ) {
		return '.git' === strtolower( substr( $package, -4, 4 ) );
	}

	/**
	 * Checks that `$package_name` matches the name in composer.json at Github.com, and return corrected value if not.
	 *
	 * @param string $package_name Package name to check.
	 * @param string $version      Optional. Package version. Defaults to empty string.
	 * @param bool   $insecure     Optional. Whether to insecurely retry downloads that failed TLS handshake. Defaults
	 *                             to false.
	 */
	private function check_github_package_name( $package_name, $version = '', $insecure = false ) {
		$github_token = getenv( 'GITHUB_TOKEN' ); // Use GITHUB_TOKEN if available to avoid authorization failures or rate-limiting.
		$headers      = $github_token ? [ 'Authorization' => 'token ' . $github_token ] : [];
		$options      = [ 'insecure' => $insecure ];

		// Generate raw git URL of composer.json file.
		$raw_content_url = "https://raw.githubusercontent.com/{$package_name}/{$this->get_raw_git_version( $version )}/composer.json";

		$response = Utils\http_request( 'GET', $raw_content_url, null /*data*/, $headers, $options );
		if ( 20 !== (int) substr( $response->status_code, 0, 2 ) ) {
			// Could not get composer.json. Possibly private so warn and return best guess from input (always xxx/xxx).
			WP_CLI::warning(
				sprintf(
					"Couldn't download composer.json file from '%s' (HTTP code %d). Presuming package name is '%s'.",
					$raw_content_url,
					$response->status_code,
					$package_name
				)
			);
			return $package_name;
		}

		// Convert composer.json JSON to Array.
		$composer_content_as_array = json_decode( $response->body, true );
		if ( null === $composer_content_as_array ) {
			WP_CLI::error( sprintf( "Failed to parse '%s' as json.", $raw_content_url ) );
		}
		if ( empty( $composer_content_as_array['name'] ) ) {
			WP_CLI::error( sprintf( "Invalid package: no name in composer.json file '%s'.", $raw_content_url ) );
		}

		// Package name in composer.json that is hosted on GitHub.
		$package_name_on_repo = $composer_content_as_array['name'];

		// If package name and repository name are not identical, then fix it.
		if ( $package_name !== $package_name_on_repo ) {
			WP_CLI::warning( sprintf( "Package name mismatch...Updating from git name '%s' to composer.json name '%s'.", $package_name, $package_name_on_repo ) );
			$package_name = $package_name_on_repo;
		}

		return $package_name;
	}

	/**
	 * Checks that `$package_name` matches the name in composer.json at the corresponding upstream repository, and return corrected value if not.
	 *
	 * @param string $package_name Package name to check.
	 * @param string $url          URL to fetch the package from.
	 * @param string $version      Optional. Package version. Defaults to empty string.
	 * @param bool   $insecure     Optional. Whether to insecurely retry downloads that failed TLS handshake. Defaults
	 *                             to false.
	 */
	private function check_git_package_name( $package_name, $url = '', $version = '', $insecure = false ) {
		if ( $url && ( strpos( $url, '://gitlab.com/' ) !== false ) || ( strpos( $url, 'git@gitlab.com:' ) !== false ) ) {
			return $this->check_gitlab_package_name( $package_name, $version, $insecure );
		}

		return $this->check_github_package_name( $package_name, $version, $insecure );
	}

	/**
	 * Checks that `$package_name` matches the name in composer.json at GitLab.com, and return corrected value if not.
	 *
	 * @param string $package_name Package name to check.
	 * @param string $version      Optional. Package version. Defaults to empty string.
	 * @param bool   $insecure     Optional. Whether to insecurely retry downloads that failed TLS handshake. Defaults
	 *                             to false.
	 */
	private function check_gitlab_package_name( $package_name, $version = '', $insecure = false ) {
		// Generate raw git URL of composer.json file.
		$raw_content_public_url  = 'https://gitlab.com/' . $package_name . '/-/raw/' . $this->get_raw_git_version( $version ) . '/composer.json';
		$raw_content_private_url = 'https://gitlab.com/api/v4/projects/' . rawurlencode( $package_name ) . '/repository/files/composer.json/raw?ref=' . $this->get_raw_git_version( $version );

		$options = [ 'insecure' => $insecure ];

		$response = Utils\http_request( 'GET', $raw_content_public_url, null /*data*/, [], $options );
		if ( $response->status_code < 200 || $response->status_code >= 300 ) {
			// Could not get composer.json. Possibly private so warn and return best guess from input (always xxx/xxx).
			WP_CLI::warning( sprintf( "Couldn't download composer.json file from '%s' (HTTP code %d). Presuming package name is '%s'.", $raw_content_public_url, $response->status_code, $package_name ) );
			return $package_name;
		}

		if ( strpos( $response->headers['content-type'], 'text/html' ) === 0 ) {
			$gitlab_token = getenv( 'GITLAB_TOKEN' ); // Use GITLAB_TOKEN if available to avoid authorization failures or rate-limiting.
			$headers      = $gitlab_token ? [ 'PRIVATE-TOKEN' => $gitlab_token ] : [];
			$response     = Utils\http_request( 'GET', $raw_content_private_url, null /*data*/, $headers, $options );

			if ( $response->status_code < 200 || $response->status_code >= 300 ) {
				// Could not get composer.json. Possibly private so warn and return best guess from input (always xxx/xxx).
				WP_CLI::warning( sprintf( "Couldn't download composer.json file from '%s' (HTTP code %d). Presuming package name is '%s'.", $raw_content_private_url, $response->status_code, $package_name ) );
				return $package_name;
			}
		}

		// Convert composer.json JSON to Array.
		$composer_content_as_array = json_decode( $response->body, true );
		if ( null === $composer_content_as_array ) {
			WP_CLI::error( sprintf( "Failed to parse '%s' as json.", $response->url ) );
		}
		if ( empty( $composer_content_as_array['name'] ) ) {
			WP_CLI::error( sprintf( "Invalid package: no name in composer.json file '%s'.", $response->url ) );
		}

		// Package name in composer.json that is hosted on Gitlab.
		$package_name_on_repo = $composer_content_as_array['name'];

		// If package name and repository name are not identical, then fix it.
		if ( $package_name !== $package_name_on_repo ) {
			WP_CLI::warning( sprintf( "Package name mismatch...Updating from git name '%s' to composer.json name '%s'.", $package_name, $package_name_on_repo ) );
			$package_name = $package_name_on_repo;
		}
		return $package_name;
	}

	/**
	 * Get the version to use for raw GitHub request. Very basic.
	 *
	 * @string $version Package version.
	 * @string Version to use for GitHub request.
	 */
	private function get_raw_git_version( $version ) {
		if ( '' === $version ) {
			return 'master';
		}

		// If Composer hash given then just use whatever's after it.
		$hash_pos = strpos( $version, '#' );
		if ( false !== $hash_pos ) {
			return substr( $version, $hash_pos + 1 );
		}

		// Strip any Composer 'dev-' prefix.
		if ( 0 === strncmp( $version, 'dev-', 4 ) ) {
			$version = substr( $version, 4 );
		}

		// Ignore/strip any relative suffixes.
		return str_replace( [ '^', '~' ], '', $version );
	}

	/**
	 * Gets the release tag for the latest stable release of a GitHub repository.
	 *
	 * @param string $package_name Name of the repository.
	 *
	 * @return string Release tag.
	 */
	private function get_github_latest_release_tag( $package_name, $insecure ) {
		$url      = "https://api.github.com/repos/{$package_name}/releases/latest";
		$options  = [ 'insecure' => $insecure ];
		$response = Utils\http_request( 'GET', $url, null, [], $options );
		if ( 20 !== (int) substr( $response->status_code, 0, 2 ) ) {
			WP_CLI::warning( 'Could not guess stable version from GitHub repository, falling back to master branch' );
			return 'master';
		}

		$package_data = json_decode( $response->body );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			WP_CLI::warning( 'Could not guess stable version from GitHub repository, falling back to master branch' );
			return 'master';
		}

		$tag = $package_data->tag_name;
		WP_CLI::debug( "Extracted latest stable release tag: {$tag}", 'packages' );

		return $tag;
	}

	/**
	 * Guesses the version constraint from a release tag.
	 *
	 * @param string $tag Release tag to guess the version constraint from.
	 *
	 * @return string Version constraint.
	 */
	private function guess_version_constraint_from_tag( $tag ) {
		$matches = [];
		if ( 1 !== preg_match( '/(?:version|v)\s*((?:[0-9]+\.?)+)(?:-.*)/i', $tag, $matches ) ) {
			return $tag;
		}

		$constraint = "^{$matches[1]}";
		WP_CLI::debug( "Guessing version constraint to use: {$constraint}", 'packages' );

		return $constraint;
	}

	/**
	 * Sets `COMPOSER_AUTH` environment variable (which Composer merges into the config setup in `Composer\Factory::createConfig()`) depending on available environment variables.
	 * Avoids authorization failures when accessing various sites.
	 */
	private function set_composer_auth_env_var() {
		$changed       = false;
		$composer_auth = getenv( 'COMPOSER_AUTH' );
		if ( false !== $composer_auth ) {
			$composer_auth = json_decode( $composer_auth, true /*assoc*/ );
		}
		if ( empty( $composer_auth ) || ! is_array( $composer_auth ) ) {
			$composer_auth = [];
		}
		$github_token = getenv( 'GITHUB_TOKEN' );
		if ( ! isset( $composer_auth['github-oauth'] ) && is_string( $github_token ) ) {
			$composer_auth['github-oauth'] = [ 'github.com' => $github_token ];
			$changed                       = true;
		}
		if ( $changed ) {
			putenv( 'COMPOSER_AUTH=' . json_encode( $composer_auth ) );
		}
	}

	/**
	 * Avoid using default Composer CA bundle if in phar as we don't include it.
	 * See https://github.com/composer/ca-bundle/blob/1.1.0/src/CaBundle.php#L64
	 */
	private function avoid_composer_ca_bundle() {
		if ( Utils\inside_phar() && ! getenv( 'SSL_CERT_FILE' ) && ! getenv( 'SSL_CERT_DIR' ) && ! ini_get( 'openssl.cafile' ) && ! ini_get( 'openssl.capath' ) ) {
			$certificate = Utils\extract_from_phar( WP_CLI_VENDOR_DIR . self::SSL_CERTIFICATE );
			putenv( "SSL_CERT_FILE={$certificate}" );
		}
	}

	/**
	 * Reads the WP-CLI packages composer.json, checking validity and returning array containing its path, contents, and decoded contents.
	 *
	 * @return array Indexed array containing the path, the contents, and the decoded contents of the WP-CLI packages composer.json.
	 */
	private function get_composer_json_path_backup_decoded() {
		$composer_json_obj = $this->get_composer_json();
		$json_path         = $composer_json_obj->getPath();
		$composer_backup   = file_get_contents( $json_path );
		if ( false === $composer_backup ) {
			$error = error_get_last();
			WP_CLI::error( sprintf( "Failed to read '%s': %s", $json_path, $error['message'] ) );
		}
		try {
			$composer_backup_decoded = $composer_json_obj->read();
		} catch ( Exception $e ) {
			WP_CLI::error( sprintf( "Failed to parse '%s' as json: %s", $json_path, $e->getMessage() ) );
		}

		return [ $json_path, $composer_backup, $composer_backup_decoded ];
	}

	/**
	 * Registers a PHP shutdown function to revert composer.json unless
	 * referenced `$revert` flag is false.
	 *
	 * @param string $json_path       Path to composer.json.
	 * @param string $composer_backup Original contents of composer.json.
	 * @param bool   &$revert         Flags whether to revert or not.
	 */
	private function register_revert_shutdown_function( $json_path, $composer_backup, &$revert ) {
		// Allocate all needed memory beforehand as much as possible.
		$revert_msg      = "Reverted composer.json.\n";
		$revert_fail_msg = "Failed to revert composer.json.\n";
		$memory_msg      = "WP-CLI ran out of memory. Please see https://bit.ly/wpclimem for further help.\n";
		$memory_string   = 'Allowed memory size of';
		$error_array     = [
			'type'    => 42,
			'message' => 'Some random dummy string to take up memory',
			'file'    => 'Another random string, which would be a filename this time',
			'line'    => 314,
		];

		register_shutdown_function(
			static function () use (
				$json_path,
				$composer_backup,
				&$revert,
				$revert_msg,
				$revert_fail_msg,
				$memory_msg,
				$memory_string,
				$error_array
			) {
				if ( $revert ) {
					if ( false !== file_put_contents( $json_path, $composer_backup ) ) {
						fwrite( STDERR, $revert_msg );
					} else {
						fwrite( STDERR, $revert_fail_msg );
					}
				}

				$error_array = error_get_last();
				if ( is_array( $error_array ) && false !== strpos( $error_array['message'], $memory_string ) ) {
					fwrite( STDERR, $memory_msg );
				}
			}
		);
	}

	/**
	 * Check whether we are dealing with Composer version 2.0.0+.
	 *
	 * @return bool
	 */
	private function is_composer_v2() {
		return version_compare( Composer::getVersion(), '2.0.0', '>=' );
	}

	/**
	 * Try to retrieve default branch via GitHub API.
	 *
	 * @param string $package_name GitHub package name to retrieve the default branch from.
	 * @param bool   $insecure     Optional. Whether to insecurely retry downloads that failed TLS handshake. Defaults
	 *                             to false.
	 * @return string Default branch, or 'master' if it could not be retrieved.
	 */
	private function get_github_default_branch( $package_name, $insecure = false ) {
		$github_token = getenv( 'GITHUB_TOKEN' ); // Use GITHUB_TOKEN if available to avoid authorization failures or rate-limiting.
		$headers      = $github_token ? [ 'Authorization' => 'token ' . $github_token ] : [];
		$options      = [ 'insecure' => $insecure ];

		$matches = [];
		if ( preg_match( '#^(?:https?://github\.com/|git@github\.com:)(?<repo_name>.*?).git$#', $package_name, $matches ) ) {
			$package_name = $matches['repo_name'];
		}

		$github_api_repo_url = "https://api.github.com/repos/{$package_name}";
		$response            = Utils\http_request( 'GET', $github_api_repo_url, null /*data*/, $headers, $options );
		if ( 20 !== (int) substr( $response->status_code, 0, 2 ) ) {
			WP_CLI::warning(
				sprintf(
					"Couldn't fetch default branch for package '%s' (HTTP code %d). Presuming default branch is 'master'.",
					$package_name,
					$response->status_code
				)
			);
			return 'master';
		}

		$package_data = json_decode( $response->body );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			WP_CLI::warning( "Couldn't fetch default branch for package '%s' (failed to decode JSON response). Presuming default branch is 'master'." );
			return 'master';
		}

		$default_branch = $package_data->default_branch;

		if ( ! is_string( $default_branch ) || empty( $default_branch ) ) {
			WP_CLI::warning(
				sprintf(
					"Couldn't fetch default branch for package '%s'. Presuming default branch is 'master'.",
					$package_name
				)
			);
			return 'master';
		}

		WP_CLI::debug( "Detected package default branch: {$default_branch}", 'packages' );

		return $default_branch;
	}
}
