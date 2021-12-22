<?php
use WP_CLI\Fetchers\User as UserFetcher;

/**
 * Lists, adds, or removes super admin users on a multisite installation.
 *
 * ## EXAMPLES
 *
 *     # List user with super-admin capabilities
 *     $ wp super-admin list
 *     supervisor
 *     administrator
 *
 *     # Grant super-admin privileges to the user.
 *     $ wp super-admin add superadmin2
 *     Success: Granted super-admin capabilities.
 *
 *     # Revoke super-admin privileges to the user.
 *     $ wp super-admin remove superadmin2
 *     Success: Revoked super-admin capabilities.
 *
 * @package wp-cli
 */
class Super_Admin_Command extends WP_CLI_Command {

	private $fields = array(
		'user_login',
	);

	public function __construct() {
		$this->fetcher = new UserFetcher();
	}

	/**
	 * Lists users with super admin capabilities.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: list
	 * options:
	 *   - list
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # List user with super-admin capabilities
	 *     $ wp super-admin list
	 *     supervisor
	 *     administrator
	 *
	 * @subcommand list
	 */
	public function list_subcommand( $_, $assoc_args ) {
		$super_admins = self::get_admins();

		if ( 'list' === $assoc_args['format'] ) {
			foreach ( $super_admins as $user_login ) {
				WP_CLI::line( $user_login );
			}
		} else {
			$output_users = array();
			foreach ( $super_admins as $user_login ) {
				$output_user = new stdClass();

				$output_user->user_login = $user_login;

				$output_users[] = $output_user;
			}
			$formatter = new \WP_CLI\Formatter( $assoc_args, $this->fields );
			$formatter->display_items( $output_users );
		}
	}

	/**
	 * Grants super admin privileges to one or more users.
	 *
	 * ## OPTIONS
	 *
	 * <user>...
	 * : One or more user IDs, user emails, or user logins.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp super-admin add superadmin2
	 *     Success: Granted super-admin capabilities.
	 */
	public function add( $args, $_ ) {

		$successes = 0;
		$errors    = 0;
		$users     = $this->fetcher->get_many( $args );
		if ( count( $users ) !== count( $args ) ) {
			$errors = count( $args ) - count( $users );
		}
		$user_logins      = wp_list_pluck( $users, 'user_login' );
		$super_admins     = self::get_admins();
		$num_super_admins = count( $super_admins );

		foreach ( $user_logins as $user_login ) {
			if ( in_array( $user_login, $super_admins, true ) ) {
				WP_CLI::warning( "User '{$user_login}' already has super-admin capabilities." );
				continue;
			}

			$super_admins[] = $user_login;
			$successes++;
		}

		if ( count( $super_admins ) === $num_super_admins ) {
			if ( $errors ) {
				$user_count = count( $args );
				WP_CLI::error( "Couldn't grant super-admin capabilities to {$errors} of {$user_count} users." );
			} else {
				WP_CLI::success( 'Super admins remain unchanged.' );
			}
		} else {
			if ( update_site_option( 'site_admins', $super_admins ) ) {
				if ( $errors ) {
					$user_count = count( $args );
					WP_CLI::error( "Only granted super-admin capabilities to {$successes} of {$user_count} users." );
				} else {
					$message = $successes > 1 ? 'users' : 'user';
					WP_CLI::success( "Granted super-admin capabilities to {$successes} {$message}." );
				}
			} else {
				WP_CLI::error( 'Site options update failed.' );
			}
		}
	}

	/**
	 * Removes super admin privileges from one or more users.
	 *
	 * ## OPTIONS
	 *
	 * <user>...
	 * : One or more user IDs, user emails, or user logins.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp super-admin remove superadmin2
	 *     Success: Revoked super-admin capabilities.
	 */
	public function remove( $args, $_ ) {
		$super_admins = self::get_admins();
		if ( ! $super_admins ) {
			WP_CLI::error( 'No super admins to revoke super-admin privileges from.' );
		}

		$users             = $this->fetcher->get_many( $args );
		$user_logins       = $users ? array_values( array_unique( wp_list_pluck( $users, 'user_login' ) ) ) : array();
		$user_logins_count = count( $user_logins );

		if ( $user_logins_count < count( $args ) ) {
			$flipped_user_logins = array_flip( $user_logins );
			// Fetcher has already warned so don't bother here, but continue with any args that are possible login names to cater for invalid users in the site options meta.

			$user_logins       = array_merge(
				$user_logins,
				array_unique(
					array_filter(
						$args,
						function ( $v ) use ( $flipped_user_logins ) {
							// Exclude numeric and email-like logins (login names can be email-like but ignore this given the circumstances).
							return ! isset( $flipped_user_logins[ $v ] ) && ! is_numeric( $v ) && ! is_email( $v );
						}
					)
				)
			);
			$user_logins_count = count( $user_logins );
		}
		if ( ! $user_logins ) {
			WP_CLI::error( 'No valid user logins given to revoke super-admin privileges from.' );
		}

		$update_super_admins = array_diff( $super_admins, $user_logins );
		if ( $update_super_admins === $super_admins ) {
			WP_CLI::error( $user_logins_count > 1 ? 'None of the given users is a super admin.' : 'The given user is not a super admin.' );
		}

		update_site_option( 'site_admins', $update_super_admins );

		$successes = count( $super_admins ) - count( $update_super_admins );
		if ( $successes === $user_logins_count ) {
			$message = $user_logins_count > 1 ? 'users' : 'user';
			$msg     = "Revoked super-admin capabilities from {$user_logins_count} {$message}.";
		} else {
			$msg = "Revoked super-admin capabilities from {$successes} of {$user_logins_count} users.";
		}
		if ( ! $update_super_admins ) {
			$msg .= ' There are no remaining super admins.';
		}
		WP_CLI::success( $msg );
	}

	private static function get_admins() {
		// We don't use get_super_admins() because we don't want to mess with the global
		return (array) get_site_option( 'site_admins', array( 'admin' ) );
	}
}
