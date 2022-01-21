<?php

use WP_CLI\ExitException;
use WP_CLI\Fetchers\User as UserFetcher;
use WP_CLI\Formatter;
use WP_CLI\Utils;

/**
 * Creates, updates, deletes, lists and retrieves application passwords.
 *
 * ## EXAMPLES
 *
 *     # List user application passwords and only show app name and password hash
 *     $ wp user application-passwords list 123 --fields=name,password
 *     +--------+------------------------------------+
 *     | name   | password                           |
 *     +--------+------------------------------------+
 *     | myapp  | $P$BVGeou1CUot114YohIemgpwxQCzb8O/ |
 *     +--------+------------------------------------+
 *
 *     # Get a specific application password and only show app name and created timestamp
 *     $ wp user application-passwords get 123 6633824d-c1d7-4f79-9dd5-4586f734d69e --fields=name,created
 *     +--------+------------+
 *     | name   | created    |
 *     +--------+------------+
 *     | myapp  | 1638395611 |
 *     +--------+------------+
 *
 *     # Create user application password
 *     $ wp user application-passwords create 123 myapp
 *     Success: Created application password.
 *     Password: ZG1bxdxdzjTwhsY8vK8l1C65
 *
 *     # Only print the password without any chrome
 *     $ wp user application-passwords create 123 myapp --porcelain
 *     ZG1bxdxdzjTwhsY8vK8l1C65
 *
 *     # Update an existing application password
 *     $ wp user application-passwords update 123 6633824d-c1d7-4f79-9dd5-4586f734d69e --name=newappname
 *     Success: Updated application password.
 *
 *     # Check if an application password for a given application exists
 *     $ wp user application-passwords exists 123 myapp
 *     $ echo $?
 *     1
 *
 *     # Bash script for checking whether an application password exists and creating one if not
 *     if ! wp user application-password exists 123 myapp; then
 *         PASSWORD=$(wp user application-password create 123 myapp --porcelain)
 *     fi
 */
final class User_Application_Password_Command {

	/**
	 * List of application password fields.
	 *
	 * @var array<string>
	 */
	const APPLICATION_PASSWORD_FIELDS = [
		'uuid',
		'app_id',
		'name',
		'password',
		'created',
		'last_used',
		'last_ip',
	];

	/**
	 * Lists all application passwords associated with a user.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : The user login, user email, or user ID of the user to get application passwords for.
	 *
	 * [--<field>=<value>]
	 * : Filter the list by a specific field.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each application password.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields.
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
	 * [--orderby=<fields>]
	 * : Set orderby which field.
	 * ---
	 * default: created
	 * options:
	 *  - uuid
	 *  - app_id
	 *  - name
	 *  - password
	 *  - created
	 *  - last_used
	 *  - last_ip
	 * ---
	 *
	 * [--order=<order>]
	 * : Set ascending or descending order.
	 * ---
	 * default: desc
	 * options:
	 *  - asc
	 *  - desc
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # List user application passwords and only show app name and password hash
	 *     $ wp user application-passwords list 123 --fields=name,password
	 *     +--------+------------------------------------+
	 *     | name   | password                           |
	 *     +--------+------------------------------------+
	 *     | myapp  | $P$BVGeou1CUot114YohIemgpwxQCzb8O/ |
	 *     +--------+------------------------------------+
	 *
	 * @subcommand list
	 *
	 * @param array $args       Indexed array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 * @throws ExitException If the user could not be found/parsed.
	 * @throws ExitException If the application passwords could not be retrieved.
	 */
	public function list_( $args, $assoc_args ) {
		$args = $this->replace_login_with_user_id( $args );

		list( $user_id ) = $args;

		$application_passwords = WP_Application_Passwords::get_user_application_passwords( $user_id );

		if ( $application_passwords instanceof WP_Error ) {
			WP_CLI::error( $application_passwords );
		}

		if ( empty( $application_passwords ) ) {
			$application_passwords = [];
		}

		$order   = Utils\get_flag_value( $assoc_args, 'order' );
		$orderby = Utils\get_flag_value( $assoc_args, 'orderby' );

		usort(
			$application_passwords,
			static function ( $a, $b ) use ( $orderby, $order ) {
				// Sort array.
				return 'asc' === $order
					? $a[ $orderby ] > $b[ $orderby ]
					: $a[ $orderby ] < $b[ $orderby ];
			}
		);

		$fields = self::APPLICATION_PASSWORD_FIELDS;

		// Avoid confusion regarding the dash/underscore usage.
		foreach ( [ 'app-id', 'last-used', 'last-ip' ] as $flag ) {
			if ( array_key_exists( $flag, $assoc_args ) ) {
				$underscored_flag                = str_replace( '-', '_', $flag );
				$assoc_args[ $underscored_flag ] = $assoc_args[ $flag ];
				unset( $assoc_args[ $flag ] );
			}
		}

		foreach ( $fields as $field ) {
			if ( ! array_key_exists( $field, $assoc_args ) ) {
				continue;
			}

			$value = Utils\get_flag_value( $assoc_args, $field );

			$application_passwords = array_filter(
				$application_passwords,
				static function ( $application_password ) use ( $field, $value ) {
					return $application_password[ $field ] === $value;
				}
			);
		}

		if ( ! empty( $assoc_args['fields'] ) ) {
			$fields = explode( ',', $assoc_args['fields'] );
		}

		$formatter = new Formatter( $assoc_args, $fields );
		$formatter->display_items( $application_passwords );
	}

	/**
	 * Gets a specific application password.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : The user login, user email, or user ID of the user to get the application password for.
	 *
	 * <uuid>
	 * : The universally unique ID of the application password.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for the application password.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields.
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
	 *     # Get a specific application password and only show app name and created timestamp
	 *     $ wp user application-passwords get 123 6633824d-c1d7-4f79-9dd5-4586f734d69e --fields=name,created
	 *     +--------+------------+
	 *     | name   | created    |
	 *     +--------+------------+
	 *     | myapp  | 1638395611 |
	 *     +--------+------------+
	 *
	 * @param array $args       Indexed array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 * @throws ExitException If the application passwords could not be retrieved.
	 */
	public function get( $args, $assoc_args ) {
		$args = $this->replace_login_with_user_id( $args );

		list( $user_id, $uuid ) = $args;

		$application_password = WP_Application_Passwords::get_user_application_password( $user_id, $uuid );

		if ( $application_password instanceof WP_Error ) {
			WP_CLI::error( $application_password );
		}

		if ( null === $application_password ) {
			WP_CLI::error( 'No application password found for this user ID and UUID.' );
		}

		$fields = self::APPLICATION_PASSWORD_FIELDS;

		if ( ! empty( $assoc_args['fields'] ) ) {
			$fields = explode( ',', $assoc_args['fields'] );
		}

		$formatter = new Formatter( $assoc_args, $fields );
		$formatter->display_items( [ $application_password ] );
	}

	/**
	 * Creates a new application password.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : The user login, user email, or user ID of the user to create a new application password for.
	 *
	 * <app-name>
	 * : Unique name of the application to create an application password for.
	 *
	 * [--app-id=<app-id>]
	 * : Application ID to attribute to the application password.
	 *
	 * [--porcelain]
	 * : Output just the new password.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create user application password
	 *     $ wp user application-passwords create 123 myapp
	 *     Success: Created application password.
	 *     Password: ZG1bxdxdzjTwhsY8vK8l1C65
	 *
	 *     # Only print the password without any chrome
	 *     $ wp user application-passwords create 123 myapp --porcelain
	 *     ZG1bxdxdzjTwhsY8vK8l1C65
	 *
	 *     # Create user application with a custom application ID for internal tracking
	 *     $ wp user application-passwords create 123 myapp --app-id=42 --porcelain
	 *     ZG1bxdxdzjTwhsY8vK8l1C65
	 *
	 * @param array $args       Indexed array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 * @throws ExitException If the application password could not be created.
	 */
	public function create( $args, $assoc_args ) {
		$args = $this->replace_login_with_user_id( $args );

		list( $user_id, $app_name ) = $args;

		$app_id = Utils\get_flag_value( $assoc_args, 'app-id', '' );

		$arguments = [
			'name'   => $app_name,
			'app_id' => $app_id,
		];

		$result = WP_Application_Passwords::create_new_application_password( $user_id, $arguments );

		if ( $result instanceof WP_Error ) {
			WP_CLI::error( $result );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain', false ) ) {
			WP_CLI::line( $result[0] );
			WP_CLI::halt( 0 );
		}

		WP_CLI::success( 'Created application password.' );
		WP_CLI::line( "Password: {$result[0]}" );
	}

	/**
	 * Updates an existing application password.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : The user login, user email, or user ID of the user to update the application password for.
	 *
	 * <uuid>
	 * : The universally unique ID of the application password.
	 *
	 * [--<field>=<value>]
	 * : Update the <field> with a new <value>. Currently supported fields: name.
	 *
	 * ## EXAMPLES
	 *
	 *     # Update an existing application password
	 *     $ wp user application-passwords update 123 6633824d-c1d7-4f79-9dd5-4586f734d69e --name=newappname
	 *     Success: Updated application password.
	 *
	 * @param array $args       Indexed array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 * @throws ExitException If the application password could not be created.
	 */
	public function update( $args, $assoc_args ) {
		$args = $this->replace_login_with_user_id( $args );

		list( $user_id, $uuid ) = $args;

		$result = WP_Application_Passwords::update_application_password( $user_id, $uuid, $assoc_args );

		if ( $result instanceof WP_Error ) {
			WP_CLI::error( $result );
		}

		WP_CLI::success( 'Updated application password.' );
	}

	/**
	 * Record usage of an application password.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : The user login, user email, or user ID of the user to update the application password for.
	 *
	 * <uuid>
	 * : The universally unique ID of the application password.
	 *
	 * ## EXAMPLES
	 *
	 *     # Record usage of an application password
	 *     $ wp user application-passwords record-usage 123 6633824d-c1d7-4f79-9dd5-4586f734d69e
	 *     Success: Recorded application password usage.
	 *
	 * @subcommand record-usage
	 *
	 * @param array $args       Indexed array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 * @throws ExitException If the application password could not be created.
	 */
	public function record_usage( $args, $assoc_args ) {
		$args = $this->replace_login_with_user_id( $args );

		list( $user_id, $uuid ) = $args;

		$result = WP_Application_Passwords::record_application_password_usage( $user_id, $uuid );

		if ( $result instanceof WP_Error ) {
			WP_CLI::error( $result );
		}

		WP_CLI::success( 'Recorded application password usage.' );
	}

	/**
	 * Delete an existing application password.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : The user login, user email, or user ID of the user to delete the application password for.
	 *
	 * [<uuid>...]
	 * : Comma-separated list of UUIDs of the application passwords to delete.
	 *
	 * [--all]
	 * : Delete all of the user's application password.
	 *
	 * ## EXAMPLES
	 *
	 *     # Record usage of an application password
	 *     $ wp user application-passwords record-usage 123 6633824d-c1d7-4f79-9dd5-4586f734d69e
	 *     Success: Recorded application password usage.
	 *
	 * @param array $args       Indexed array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 * @throws ExitException If the application password could not be created.
	 */
	public function delete( $args, $assoc_args ) {
		$args = $this->replace_login_with_user_id( $args );

		$user_id = array_shift( $args );
		$all     = Utils\get_flag_value( $assoc_args, 'all', false );
		$count   = count( $args );

		if ( ( 0 < $count && true === $all ) || ( 0 === $count && true !== $all ) ) {
			WP_CLI::error( 'You need to specify either one or more UUIDS or provide the --all flag' );
		}

		if ( true === $all ) {
			$result = WP_Application_Passwords::delete_all_application_passwords( $user_id );

			if ( $result instanceof WP_Error ) {
				WP_CLI::error( $result );
			}

			WP_CLI::success( 'Deleted all application passwords.' );
			WP_CLI::halt( 0 );
		}

		$errors = 0;
		foreach ( $args as $uuid ) {
			$result = WP_Application_Passwords::delete_application_password( $user_id, $uuid );

			if ( $result instanceof WP_Error ) {
				WP_CLI::warning( "Failed to delete UUID {$uuid}: " . $result->get_error_message() );
				$errors++;
			}
		}

		WP_CLI::success(
			sprintf(
				'Deleted %d of %d application %s.',
				$count - $errors,
				$count,
				Utils\pluralize( 'password', $count )
			)
		);

		WP_CLI::halt( 0 === $errors ? 0 : 1 );
	}

	/**
	 * Checks whether an application password for a given application exists.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : The user login, user email, or user ID of the user to check the existence of an application password for.
	 *
	 * <app-name>
	 * : Name of the application to check the existence of an application password for.
	 *
	 * ## EXAMPLES
	 *
	 *     # Check if an application password for a given application exists
	 *     $ wp user application-passwords exists 123 myapp
	 *     $ echo $?
	 *     1
	 *
	 *     # Bash script for checking whether an application password exists and creating one if not
	 *     if ! wp user application-password exists 123 myapp; then
	 *         PASSWORD=$(wp user application-password create 123 myapp --porcelain)
	 *     fi
	 *
	 * @param array $args       Indexed array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 * @throws ExitException If the application password could not be created.
	 */
	public function exists( $args, $assoc_args ) {
		$args = $this->replace_login_with_user_id( $args );

		list( $user_id, $app_name ) = $args;

		$result = $this->application_name_exists_for_user( $user_id, $app_name );

		if ( $result instanceof WP_Error ) {
			WP_CLI::error( $result );
		}

		WP_CLI::halt( $result ? 0 : 1 );
	}

	/**
	 * Replaces user_login value with user ID.
	 *
	 * @param array $args Associative array of arguments.
	 * @return array Associative array of arguments with the user login replaced with an ID.
	 * @throws ExitException If the user is not found.
	 */
	private function replace_login_with_user_id( $args ) {
		$user    = ( new UserFetcher() )->get_check( $args[0] );
		$args[0] = $user->ID;

		return $args;
	}

	/**
	 * Checks if application name exists for the given user.
	 *
	 * This is a polyfill for WP_Application_Passwords::get_user_application_passwords(), which was only added for
	 * WordPress 5.7+, but we're already supporting application passwords for WordPress 5.6+.
	 *
	 * @param int    $user_id  User ID to check the application passwords for.
	 * @param string $app_name Application name to look for.
	 * @return bool
	 */
	private function application_name_exists_for_user( $user_id, $app_name ) {
		if ( Utils\wp_version_compare( '5.7', '<' ) ) {
			$passwords = WP_Application_Passwords::get_user_application_passwords( $user_id );

			foreach ( $passwords as $password ) {
				if ( strtolower( $password['name'] ) === strtolower( $name ) ) {
					return true;
				}
			}

			return false;
		}

		return WP_Application_Passwords::application_name_exists_for_user( $user_id, $app_name );
	}
}
