<?php

use WP_CLI\CommandWithUpgrade;
use WP_CLI\ParseThemeNameInput;
use WP_CLI\Utils;

/**
 * Manages themes, including installs, activations, and updates.
 *
 * See the WordPress [Theme Handbook](https://developer.wordpress.org/themes/) developer resource for more information on themes.
 *
 * ## EXAMPLES
 *
 *     # Install the latest version of a theme from wordpress.org and activate
 *     $ wp theme install twentysixteen --activate
 *     Installing Twenty Sixteen (1.2)
 *     Downloading install package from http://downloads.wordpress.org/theme/twentysixteen.1.2.zip...
 *     Unpacking the package...
 *     Installing the theme...
 *     Theme installed successfully.
 *     Activating 'twentysixteen'...
 *     Success: Switched to 'Twenty Sixteen' theme.
 *
 *     # Get details of an installed theme
 *     $ wp theme get twentysixteen --fields=name,title,version
 *     +---------+----------------+
 *     | Field   | Value          |
 *     +---------+----------------+
 *     | name    | Twenty Sixteen |
 *     | title   | Twenty Sixteen |
 *     | version | 1.2            |
 *     +---------+----------------+
 *
 *     # Get status of theme
 *     $ wp theme status twentysixteen
 *     Theme twentysixteen details:
 *          Name: Twenty Sixteen
 *          Status: Active
 *          Version: 1.2
 *          Author: the WordPress team
 *
 * @package wp-cli
 */
class Theme_Command extends CommandWithUpgrade {

	use ParseThemeNameInput;

	protected $item_type         = 'theme';
	protected $upgrade_refresh   = 'wp_update_themes';
	protected $upgrade_transient = 'update_themes';

	protected $obj_fields = [ 'name', 'status', 'update', 'version' ];

	public function __construct() {
		if ( is_multisite() ) {
			$this->obj_fields[] = 'enabled';
		}
		parent::__construct();

		$this->fetcher = new WP_CLI\Fetchers\Theme();
	}

	protected function get_upgrader_class( $force ) {
		return $force ? '\\WP_CLI\\DestructiveThemeUpgrader' : 'Theme_Upgrader';
	}

	/**
	 * Reveals the status of one or all themes.
	 *
	 * ## OPTIONS
	 *
	 * [<theme>]
	 * : A particular theme to show the status for.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp theme status twentysixteen
	 *     Theme twentysixteen details:
	 *          Name: Twenty Sixteen
	 *          Status: Inactive
	 *          Version: 1.2
	 *          Author: the WordPress team
	 */
	public function status( $args ) {
		if ( isset( $args[0] ) ) {
			$theme  = $this->fetcher->get_check( $args[0] );
			$errors = $theme->errors();
			if ( is_wp_error( $errors ) ) {
				$message = $errors->get_error_message();
				WP_CLI::error( $message );
			}
		}

		parent::status( $args );
	}

	/**
	 * Searches the WordPress.org theme directory.
	 *
	 * Displays themes in the WordPress.org theme directory matching a given
	 * search query.
	 *
	 * ## OPTIONS
	 *
	 * <search>
	 * : The string to search for.
	 *
	 * [--page=<page>]
	 * : Optional page to display.
	 * ---
	 * default: 1
	 * ---
	 *
	 * [--per-page=<per-page>]
	 * : Optional number of results to display. Defaults to 10.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each theme.
	 *
	 * [--fields=<fields>]
	 * : Ask for specific fields from the API. Defaults to name,slug,author,rating. Acceptable values:
	 *
	 *     **name**: Theme Name
	 *     **slug**: Theme Slug
	 *     **version**: Current Version Number
	 *     **author**: Theme Author
	 *     **preview_url**: Theme Preview URL
	 *     **screenshot_url**: Theme Screenshot URL
	 *     **rating**: Theme Rating
	 *     **num_ratings**: Number of Theme Ratings
	 *     **homepage**: Theme Author's Homepage
	 *     **description**: Theme Description
	 *     **url**: Theme's URL on wordpress.org
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp theme search photo --per-page=6
	 *     Success: Showing 6 of 203 themes.
	 *     +----------------------+----------------------+--------+
	 *     | name                 | slug                 | rating |
	 *     +----------------------+----------------------+--------+
	 *     | Photos               | photos               | 100    |
	 *     | Infinite Photography | infinite-photography | 100    |
	 *     | PhotoBook            | photobook            | 100    |
	 *     | BG Photo Frame       | bg-photo-frame       | 0      |
	 *     | fPhotography         | fphotography         | 0      |
	 *     | Photo Perfect        | photo-perfect        | 98     |
	 *     +----------------------+----------------------+--------+
	 */
	public function search( $args, $assoc_args ) {
		parent::_search( $args, $assoc_args );
	}

	protected function status_single( $args ) {
		$theme = $this->fetcher->get_check( $args[0] );

		$status = $this->format_status( $this->get_status( $theme ), 'long' );

		$version = $theme->get( 'Version' );
		if ( $this->has_update( $theme->get_stylesheet() ) ) {
			$version .= ' (%gUpdate available%n)';
		}

		echo WP_CLI::colorize(
			Utils\mustache_render(
				self::get_template_path( 'theme-status.mustache' ),
				[
					'slug'    => $theme->get_stylesheet(),
					'status'  => $status,
					'version' => $version,
					'name'    => $theme->get( 'Name' ),
					'author'  => $theme->get( 'Author' ),
				]
			)
		);
	}

	protected function get_all_items() {
		return $this->get_item_list();
	}

	/**
	 * Activates a theme.
	 *
	 * ## OPTIONS
	 *
	 * <theme>
	 * : The theme to activate.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp theme activate twentysixteen
	 *     Success: Switched to 'Twenty Sixteen' theme.
	 */
	public function activate( $args = array() ) {
		$theme = $this->fetcher->get_check( $args[0] );

		$errors = $theme->errors();
		if ( is_wp_error( $errors ) ) {
			$message = $errors->get_error_message();
			WP_CLI::error( $message );
		}

		$name = $theme->get( 'Name' );

		if ( 'active' === $this->get_status( $theme ) ) {
			WP_CLI::warning( "The '$name' theme is already active." );
			return;
		}

		if ( $theme->get_stylesheet() !== $theme->get_template() && ! $this->fetcher->get( $theme->get_template() ) ) {
			WP_CLI::error( "The '{$theme->get_stylesheet()}' theme cannot be activated without its parent, '{$theme->get_template()}'." );
		}

		switch_theme( $theme->get_template(), $theme->get_stylesheet() );

		if ( $this->is_active_theme( $theme ) ) {
			WP_CLI::success( "Switched to '$name' theme." );
		} else {
			WP_CLI::error( "Could not switch to '$name' theme." );
		}
	}

	/**
	 * Enables a theme on a WordPress multisite install.
	 *
	 * Permits theme to be activated from the dashboard of a site on a WordPress
	 * multisite install.
	 *
	 * ## OPTIONS
	 *
	 * <theme>
	 * : The theme to enable.
	 *
	 * [--network]
	 * : If set, the theme is enabled for the entire network
	 *
	 * [--activate]
	 * : If set, the theme is activated for the current site. Note that
	 * the "network" flag has no influence on this.
	 *
	 * ## EXAMPLES
	 *
	 *     # Enable theme
	 *     $ wp theme enable twentysixteen
	 *     Success: Enabled the 'Twenty Sixteen' theme.
	 *
	 *     # Network enable theme
	 *     $ wp theme enable twentysixteen --network
	 *     Success: Network enabled the 'Twenty Sixteen' theme.
	 *
	 *     # Network enable and activate theme for current site
	 *     $ wp theme enable twentysixteen --activate
	 *     Success: Enabled the 'Twenty Sixteen' theme.
	 *     Success: Switched to 'Twenty Sixteen' theme.
	 */
	public function enable( $args, $assoc_args ) {
		if ( ! is_multisite() ) {
			WP_CLI::error( 'This is not a multisite installation.' );
		}

		$theme = $this->fetcher->get_check( $args[0] );
		$name  = $theme->get( 'Name' );

		# If the --network flag is set, we'll be calling the (get|update)_site_option functions
		$_site = ! empty( $assoc_args['network'] ) ? '_site' : '';

		# Add the current theme to the allowed themes option or site option
		$allowed_themes = call_user_func( "get{$_site}_option", 'allowedthemes' );
		if ( empty( $allowed_themes ) ) {
			$allowed_themes = array();
		}
		$allowed_themes[ $theme->get_stylesheet() ] = true;
		call_user_func( "update{$_site}_option", 'allowedthemes', $allowed_themes );

		if ( ! empty( $assoc_args['network'] ) ) {
			WP_CLI::success( "Network enabled the '$name' theme." );
		} else {
			WP_CLI::success( "Enabled the '$name' theme." );
		}

		# If the --activate flag is set, activate the theme for the current site
		if ( ! empty( $assoc_args['activate'] ) ) {
			$this->activate( $args );
		}
	}

	/**
	 * Disables a theme on a WordPress multisite install.
	 *
	 * Removes ability for a theme to be activated from the dashboard of a site
	 * on a WordPress multisite install.
	 *
	 * ## OPTIONS
	 *
	 * <theme>
	 * : The theme to disable.
	 *
	 * [--network]
	 * : If set, the theme is disabled on the network level. Note that
	 * individual sites may still have this theme enabled if it was
	 * enabled for them independently.
	 *
	 * ## EXAMPLES
	 *
	 *     # Disable theme
	 *     $ wp theme disable twentysixteen
	 *     Success: Disabled the 'Twenty Sixteen' theme.
	 *
	 *     # Disable theme in network level
	 *     $ wp theme disable twentysixteen --network
	 *     Success: Network disabled the 'Twenty Sixteen' theme.
	 */
	public function disable( $args, $assoc_args ) {
		if ( ! is_multisite() ) {
			WP_CLI::error( 'This is not a multisite installation.' );
		}

		$theme = $this->fetcher->get_check( $args[0] );
		$name  = $theme->get( 'Name' );

		# If the --network flag is set, we'll be calling the (get|update)_site_option functions
		$_site = ! empty( $assoc_args['network'] ) ? '_site' : '';

		# Add the current theme to the allowed themes option or site option
		$allowed_themes = call_user_func( "get{$_site}_option", 'allowedthemes' );
		if ( ! empty( $allowed_themes[ $theme->get_stylesheet() ] ) ) {
			unset( $allowed_themes[ $theme->get_stylesheet() ] );
		}
		call_user_func( "update{$_site}_option", 'allowedthemes', $allowed_themes );

		if ( ! empty( $assoc_args['network'] ) ) {
			WP_CLI::success( "Network disabled the '$name' theme." );
		} else {
			WP_CLI::success( "Disabled the '$name' theme." );
		}
	}

	/**
	 * Gets the path to a theme or to the theme directory.
	 *
	 * ## OPTIONS
	 *
	 * [<theme>]
	 * : The theme to get the path to. Path includes "style.css" file.
	 * If not set, will return the path to the themes directory.
	 *
	 * [--dir]
	 * : If set, get the path to the closest parent directory, instead of the
	 * theme's "style.css" file.
	 *
	 * ## EXAMPLES
	 *
	 *     # Get theme path
	 *     $ wp theme path
	 *     /var/www/example.com/public_html/wp-content/themes
	 *
	 *     # Change directory to theme path
	 *     $ cd $(wp theme path)
	 */
	public function path( $args, $assoc_args ) {
		if ( empty( $args ) ) {
			$path = WP_CONTENT_DIR . '/themes';
		} else {
			$theme = $this->fetcher->get_check( $args[0] );

			$path = $theme->get_stylesheet_directory();

			if ( ! Utils\get_flag_value( $assoc_args, 'dir' ) ) {
				$path .= '/style.css';
			}
		}

		WP_CLI::line( $path );
	}

	protected function install_from_repo( $slug, $assoc_args ) {
		$api = themes_api( 'theme_information', array( 'slug' => $slug ) );

		if ( is_wp_error( $api ) ) {
			return $api;
		}

		if ( isset( $assoc_args['version'] ) ) {
			self::alter_api_response( $api, $assoc_args['version'] );
		}

		if ( ! Utils\get_flag_value( $assoc_args, 'force' ) ) {
			$theme = wp_get_theme( $slug );
			if ( $theme->exists() ) {
				// We know this will fail, so avoid a needless download of the package.
				return new WP_Error( 'already_installed', 'Theme already installed.' );
			}
			// Clear cache so WP_Theme doesn't create a "missing theme" object.
			$cache_hash = md5( $theme->theme_root . '/' . $theme->stylesheet );
			foreach ( [ 'theme', 'screenshot', 'headers', 'page_templates' ] as $key ) {
				wp_cache_delete( $key . '-' . $cache_hash, 'themes' );
			}
		}

		WP_CLI::log( sprintf( 'Installing %s (%s)', html_entity_decode( $api->name, ENT_QUOTES ), $api->version ) );
		if ( Utils\get_flag_value( $assoc_args, 'version' ) !== 'dev' ) {
			WP_CLI::get_http_cache_manager()->whitelist_package( $api->download_link, $this->item_type, $api->slug, $api->version );
		}

		// Ignore failures on accessing SSL "https://api.wordpress.org/themes/update-check/1.1/" in `wp_update_themes()` which seem to occur intermittently.
		set_error_handler( array( __CLASS__, 'error_handler' ), E_USER_WARNING | E_USER_NOTICE );

		$result = $this->get_upgrader( $assoc_args )->install( $api->download_link );

		restore_error_handler();

		return $result;
	}

	protected function get_item_list() {
		return $this->get_all_themes();
	}

	protected function filter_item_list( $items, $args ) {
		$theme_files = array();
		foreach ( $args as $arg ) {
			$theme_files[] = $this->fetcher->get_check( $arg )->get_stylesheet_directory();
		}

		return Utils\pick_fields( $items, $theme_files );
	}

	/**
	 * Installs one or more themes.
	 *
	 * ## OPTIONS
	 *
	 * <theme|zip|url>...
	 * : One or more themes to install. Accepts a theme slug, the path to a local zip file, or a URL to a remote zip file.
	 *
	 * [--version=<version>]
	 * : If set, get that particular version from wordpress.org, instead of the
	 * stable version.
	 *
	 * [--force]
	 * : If set, the command will overwrite any installed version of the theme, without prompting
	 * for confirmation.
	 *
	 * [--activate]
	 * : If set, the theme will be activated immediately after install.
	 *
	 * [--insecure]
	 * : Retry downloads without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.
	 *
	 * ## EXAMPLES
	 *
	 *     # Install the latest version from wordpress.org and activate
	 *     $ wp theme install twentysixteen --activate
	 *     Installing Twenty Sixteen (1.2)
	 *     Downloading install package from http://downloads.wordpress.org/theme/twentysixteen.1.2.zip...
	 *     Unpacking the package...
	 *     Installing the theme...
	 *     Theme installed successfully.
	 *     Activating 'twentysixteen'...
	 *     Success: Switched to 'Twenty Sixteen' theme.
	 *
	 *     # Install from a local zip file
	 *     $ wp theme install ../my-theme.zip
	 *
	 *     # Install from a remote zip file
	 *     $ wp theme install http://s3.amazonaws.com/bucketname/my-theme.zip?AWSAccessKeyId=123&Expires=456&Signature=abcdef
	 */
	public function install( $args, $assoc_args ) {

		$theme_root = get_theme_root();
		if ( $theme_root && ! is_dir( $theme_root ) ) {
			wp_mkdir_p( $theme_root );
			register_theme_directory( $theme_root );
		}

		parent::install( $args, $assoc_args );
	}

	/**
	 * Gets details about a theme.
	 *
	 * ## OPTIONS
	 *
	 * <theme>
	 * : The theme to get.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole theme, returns the value of a single field.
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
	 *     $ wp theme get twentysixteen --fields=name,title,version
	 *     +---------+----------------+
	 *     | Field   | Value          |
	 *     +---------+----------------+
	 *     | name    | Twenty Sixteen |
	 *     | title   | Twenty Sixteen |
	 *     | version | 1.2            |
	 *     +---------+----------------+
	 */
	public function get( $args, $assoc_args ) {
		$theme = $this->fetcher->get_check( $args[0] );

		$errors = $theme->errors();
		if ( is_wp_error( $errors ) ) {
			$message = $errors->get_error_message();
			WP_CLI::error( $message );
		}

		// WP_Theme object employs magic getter, unfortunately.
		$theme_vars = [
			'name',
			'title',
			'version',
			'status',
			'parent_theme',
			'template_dir',
			'stylesheet_dir',
			'template',
			'stylesheet',
			'screenshot',
			'description',
			'author',
			'tags',
			'theme_root',
			'theme_root_uri',
		];
		$theme_obj  = new stdClass();
		foreach ( $theme_vars as $var ) {
			$theme_obj->$var = $theme->$var;
		}

		$theme_obj->status      = $this->get_status( $theme );
		$theme_obj->description = wordwrap( $theme_obj->description );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = $theme_vars;
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $theme_obj );
	}

	/**
	 * Updates one or more themes.
	 *
	 * ## OPTIONS
	 *
	 * [<theme>...]
	 * : One or more themes to update.
	 *
	 * [--all]
	 * : If set, all themes that have updates will be updated.
	 *
	 * [--exclude=<theme-names>]
	 * : Comma separated list of theme names that should be excluded from updating.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - summary
	 * ---
	 *
	 * [--version=<version>]
	 * : If set, the theme will be updated to the specified version.
	 *
	 * [--dry-run]
	 * : Preview which themes would be updated.
	 *
	 * [--insecure]
	 * : Retry downloads without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.
	 *
	 * ## EXAMPLES
	 *
	 *     # Update multiple themes
	 *     $ wp theme update twentyfifteen twentysixteen
	 *     Downloading update from https://downloads.wordpress.org/theme/twentyfifteen.1.5.zip...
	 *     Unpacking the update...
	 *     Installing the latest version...
	 *     Removing the old version of the theme...
	 *     Theme updated successfully.
	 *     Downloading update from https://downloads.wordpress.org/theme/twentysixteen.1.2.zip...
	 *     Unpacking the update...
	 *     Installing the latest version...
	 *     Removing the old version of the theme...
	 *     Theme updated successfully.
	 *     +---------------+-------------+-------------+---------+
	 *     | name          | old_version | new_version | status  |
	 *     +---------------+-------------+-------------+---------+
	 *     | twentyfifteen | 1.4         | 1.5         | Updated |
	 *     | twentysixteen | 1.1         | 1.2         | Updated |
	 *     +---------------+-------------+-------------+---------+
	 *     Success: Updated 2 of 2 themes.
	 *
	 *     # Exclude themes updates when bulk updating the themes
	 *     $ wp theme update --all --exclude=twentyfifteen
	 *     Downloading update from https://downloads.wordpress.org/theme/astra.1.0.5.1.zip...
	 *     Unpacking the update...
	 *     Installing the latest version...
	 *     Removing the old version of the theme...
	 *     Theme updated successfully.
	 *     Downloading update from https://downloads.wordpress.org/theme/twentyseventeen.1.2.zip...
	 *     Unpacking the update...
	 *     Installing the latest version...
	 *     Removing the old version of the theme...
	 *     Theme updated successfully.
	 *     +-----------------+----------+---------+----------------+
	 *     | name            | status   | version | update_version |
	 *     +-----------------+----------+---------+----------------+
	 *     | astra           | inactive | 1.0.1   | 1.0.5.1        |
	 *     | twentyseventeen | inactive | 1.1     | 1.2            |
	 *     +-----------------+----------+---------+----------------+
	 *     Success: Updated 2 of 2 themes.
	 *
	 *     # Update all themes
	 *     $ wp theme update --all
	 *
	 * @alias upgrade
	 */
	public function update( $args, $assoc_args ) {
		$all = Utils\get_flag_value( $assoc_args, 'all', false );

		$args = $this->check_optional_args_and_all( $args, $all );
		if ( ! $args ) {
			return;
		}

		if ( isset( $assoc_args['version'] ) && isset( $assoc_args['dry-run'] ) ) {
			WP_CLI::error( '--dry-run cannot be used together with --version.' );
		}

		if ( isset( $assoc_args['version'] ) ) {
			foreach ( $this->fetcher->get_many( $args ) as $theme ) {
				$r = delete_theme( $theme->stylesheet );
				if ( is_wp_error( $r ) ) {
					WP_CLI::warning( $r );
				} else {
					$assoc_args['force'] = true;
					$this->install( array( $theme->stylesheet ), $assoc_args );
				}
			}
		} else {
			parent::update_many( $args, $assoc_args );
		}
	}

	/**
	 * Checks if a given theme is installed.
	 *
	 * Returns exit code 0 when installed, 1 when uninstalled.
	 *
	 * ## OPTIONS
	 *
	 * <theme>
	 * : The theme to check.
	 *
	 * ## EXAMPLES
	 *
	 *     # Check whether theme is installed; exit status 0 if installed, otherwise 1
	 *     $ wp theme is-installed hello
	 *     $ echo $?
	 *     1
	 *
	 * @subcommand is-installed
	 */
	public function is_installed( $args, $assoc_args = array() ) {
		$theme = wp_get_theme( $args[0] );

		if ( $theme->exists() ) {
			WP_CLI::halt( 0 );
		} else {
			WP_CLI::halt( 1 );
		}
	}

	/**
	 * Checks if a given theme is active.
	 *
	 * Returns exit code 0 when active, 1 when not active.
	 *
	 * ## OPTIONS
	 *
	 * <theme>
	 * : The plugin to check.
	 *
	 * ## EXAMPLES
	 *
	 *     # Check whether theme is Active; exit status 0 if active, otherwise 1
	 *     $ wp theme is-active twentyfifteen
	 *     $ echo $?
	 *     1
	 *
	 * @subcommand is-active
	 */
	public function is_active( $args, $assoc_args = array() ) {
		$theme = wp_get_theme( $args[0] );

		if ( ! $theme->exists() ) {
			WP_CLI::halt( 1 );
		}

		$this->is_active_theme( $theme ) ? WP_CLI::halt( 0 ) : WP_CLI::halt( 1 );
	}

	/**
	 * Deletes one or more themes.
	 *
	 * Removes the theme or themes from the filesystem.
	 *
	 * ## OPTIONS
	 *
	 * [<theme>...]
	 * : One or more themes to delete.
	 *
	 * [--all]
	 * : If set, all themes will be deleted except active theme.
	 *
	 * [--force]
	 * : To delete active theme use this.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp theme delete twentytwelve
	 *     Deleted 'twentytwelve' theme.
	 *     Success: Deleted 1 of 1 themes.
	 *
	 * @alias uninstall
	 */
	public function delete( $args, $assoc_args ) {

		$all = Utils\get_flag_value( $assoc_args, 'all', false );

		$args = $this->check_optional_args_and_all( $args, $all, 'delete' );
		if ( ! $args ) {
			return;
		}

		$force = Utils\get_flag_value( $assoc_args, 'force', false );

		$successes = 0;
		$errors    = 0;
		foreach ( $this->fetcher->get_many( $args ) as $theme ) {
			$theme_slug = $theme->get_stylesheet();

			if ( $this->is_active_theme( $theme ) && ! $force ) {
				if ( ! $all ) {
					WP_CLI::warning( "Can't delete the currently active theme: $theme_slug" );
					$errors++;
				}
				continue;
			}

			$r = delete_theme( $theme_slug );

			if ( is_wp_error( $r ) ) {
				WP_CLI::warning( $r );
				$errors++;
			} else {
				WP_CLI::log( "Deleted '$theme_slug' theme." );
				$successes++;
			}
		}
		if ( ! $this->chained_command ) {
			Utils\report_batch_operation_results( 'theme', 'delete', count( $args ), $successes, $errors );
		}
	}

	/**
	 * Gets a list of themes.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Filter results based on the value of a field.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each theme.
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
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * [--status=<status>]
	 * : Filter the output by theme status.
	 * ---
	 * options:
	 *   - active
	 *   - parent
	 *   - inactive
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each theme:
	 *
	 * * name
	 * * status
	 * * update
	 * * version
	 *
	 * These fields are optionally available:
	 *
	 * * update_version
	 * * update_package
	 * * update_id
	 * * title
	 * * description
	 *
	 * ## EXAMPLES
	 *
	 *     # List themes
	 *     $ wp theme list --status=inactive --format=csv
	 *     name,status,update,version
	 *     twentyfourteen,inactive,none,1.7
	 *     twentysixteen,inactive,available,1.1
	 *
	 * @subcommand list
	 */
	public function list_( $_, $assoc_args ) {
		parent::_list( $_, $assoc_args );
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
}
