<?php

use WP_CLI\Formatter;

/**
 * Adds, removes, and lists capabilities of a user role.
 *
 * See references for [Roles and Capabilities](https://codex.wordpress.org/Roles_and_Capabilities) and [WP User class](https://codex.wordpress.org/Class_Reference/WP_User).
 *
 * ## EXAMPLES
 *
 *     # Add 'spectate' capability to 'author' role.
 *     $ wp cap add 'author' 'spectate'
 *     Success: Added 1 capability to 'author' role.
 *
 *     # Add all caps from 'editor' role to 'author' role.
 *     $ wp cap list 'editor' | xargs wp cap add 'author'
 *     Success: Added 24 capabilities to 'author' role.
 *
 *     # Remove all caps from 'editor' role that also appear in 'author' role.
 *     $ wp cap list 'author' | xargs wp cap remove 'editor'
 *     Success: Removed 34 capabilities from 'editor' role.
 */
class Capabilities_Command extends WP_CLI_Command {

	/**
	 * List of available fields.
	 *
	 * @var array
	 */
	private $fields = [ 'name' ];

	/**
	 * Lists capabilities for a given role.
	 *
	 * ## OPTIONS
	 *
	 * <role>
	 * : Key for the role.
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
	 * [--show-grant]
	 * : Display all capabilities defined for a role including grant.
	 * ---
	 * default: false
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Display alphabetical list of Contributor capabilities.
	 *     $ wp cap list 'contributor' | sort
	 *     delete_posts
	 *     edit_posts
	 *     level_0
	 *     level_1
	 *     read
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$role_obj = self::get_role( $args[0] );

		$show_grant = ! empty( $assoc_args['show-grant'] );

		if ( $show_grant ) {
			array_push( $this->fields, 'grant' );
			$capabilities = $role_obj->capabilities;
		} else {
			$capabilities = array_filter( $role_obj->capabilities );
		}

		$output_caps = array();
		foreach ( $capabilities as $cap => $grant ) {
			$output_cap = new stdClass();

			$output_cap->name  = $cap;
			$output_cap->grant = $grant ? 'true' : 'false';

			$output_caps[] = $output_cap;
		}

		if ( 'list' === $assoc_args['format'] ) {
			foreach ( $output_caps as $cap ) {
				if ( $show_grant ) {
					WP_CLI::line( implode( ',', array( $cap->name, $cap->grant ) ) );
				} else {
					WP_CLI::line( $cap->name );
				}
			}
		} else {
			$formatter = new Formatter( $assoc_args, $this->fields );
			$formatter->display_items( $output_caps );
		}
	}

	/**
	 * Adds capabilities to a given role.
	 *
	 * ## OPTIONS
	 *
	 * <role>
	 * : Key for the role.
	 *
	 * <cap>...
	 * : One or more capabilities to add.
	 *
	 * [--grant]
	 * : Adds the capability as an explicit boolean value, instead of implicitly defaulting to `true`.
	 * ---
	 * default: true
	 * options:
	 *   - true
	 *   - false
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Add 'spectate' capability to 'author' role.
	 *     $ wp cap add author spectate
	 *     Success: Added 1 capability to 'author' role.
	 */
	public function add( $args, $assoc_args ) {
		self::persistence_check();

		$role = array_shift( $args );

		$role_obj = self::get_role( $role );

		$grant = ! isset( $assoc_args['grant'] ) || ! empty( $assoc_args['grant'] );

		$count = 0;

		foreach ( $args as $cap ) {
			if ( true === $grant && $role_obj->has_cap( $cap ) ) {
				continue;
			}

			if ( false === $grant && isset( $role_obj->capabilities[ $cap ] ) && false === $role_obj->capabilities[ $cap ] ) {
				continue;
			}

			$role_obj->add_cap( $cap, $grant );

			$count++;
		}

		$capability          = WP_CLI\Utils\pluralize( 'capability', $count );
		$grant_qualification = $grant ? '' : ' as false';

		WP_CLI::success( "Added {$count} {$capability} to '{$role}' role{$grant_qualification}." );
	}

	/**
	 * Removes capabilities from a given role.
	 *
	 * ## OPTIONS
	 *
	 * <role>
	 * : Key for the role.
	 *
	 * <cap>...
	 * : One or more capabilities to remove.
	 *
	 * ## EXAMPLES
	 *
	 *     # Remove 'spectate' capability from 'author' role.
	 *     $ wp cap remove author spectate
	 *     Success: Removed 1 capability from 'author' role.
	 */
	public function remove( $args ) {
		self::persistence_check();

		$role = array_shift( $args );

		$role_obj = self::get_role( $role );

		$count = 0;

		foreach ( $args as $cap ) {
			if ( ! isset( $role_obj->capabilities[ $cap ] ) ) {
				continue;
			}

			$role_obj->remove_cap( $cap );

			$count++;
		}

		$capability = WP_CLI\Utils\pluralize( 'capability', $count );

		WP_CLI::success( "Removed {$count} {$capability} from '{$role}' role." );
	}

	/**
	 * Retrieve a specific role from the system.
	 *
	 * @param string $role Role to retrieve.
	 * @return WP_Role Requested role.
	 * @throws \WP_CLI\ExitException If the role could not be found.
	 */
	private static function get_role( $role ) {
		global $wp_roles;

		$role_obj = $wp_roles->get_role( $role );

		if ( ! $role_obj ) {
			WP_CLI::error( "'{$role}' role not found." );
		}

		return $role_obj;
	}

	/**
	 * Assert that the roles are persisted to the database.
	 *
	 * @throws \WP_CLI\ExitException If the roles are not persisted to the
	 *                               database.
	 */
	private static function persistence_check() {
		global $wp_roles;

		if ( ! $wp_roles->use_db ) {
			WP_CLI::error( 'Role definitions are not persistent.' );
		}
	}
}
