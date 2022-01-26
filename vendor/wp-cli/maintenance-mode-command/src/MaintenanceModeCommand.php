<?php

namespace WP_CLI\MaintenanceMode;

use WP_CLI;
use WP_CLI_Command;
use WP_Upgrader;
use WP_Filesystem_Base;

/**
 * Activates, deactivates or checks the status of the maintenance mode of a site.
 *
 * ## EXAMPLES
 *
 *     # Activate Maintenance mode.
 *     $ wp maintenance-mode activate
 *     Enabling Maintenance mode...
 *     Success: Activated Maintenance mode.
 *
 *     # Deactivate Maintenance mode.
 *     $ wp maintenance-mode deactivate
 *     Disabling Maintenance mode...
 *     Success: Deactivated Maintenance mode.
 *
 *     # Display Maintenance mode status.
 *     $ wp maintenance-mode status
 *     Maintenance mode is active.
 *
 *     # Get Maintenance mode status for scripting purpose.
 *     $ wp maintenance-mode is-active
 *     $ echo $?
 *     1
 *
 * @when    after_wp_load
 * @package wp-cli
 */
class MaintenanceModeCommand extends WP_CLI_Command {


	/**
	 * Instance of WP_Upgrader.
	 *
	 * @var WP_Upgrader
	 */
	private $upgrader;

	/**
	 * Instantiate a MaintenanceModeCommand object.
	 */
	public function __construct() {
		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		$this->upgrader = new WP_Upgrader( new WP_CLI\UpgraderSkin() );
		$this->upgrader->init();
	}

	/**
	 * Activates maintenance mode.
	 *
	 * [--force]
	 * : Force maintenance mode activation operation.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp maintenance-mode activate
	 *     Enabling Maintenance mode...
	 *     Success: Activated Maintenance mode.
	 */
	public function activate( $_, $assoc_args ) {
		if ( $this->get_maintenance_mode_status() && ! WP_CLI\Utils\get_flag_value( $assoc_args, 'force' ) ) {
			WP_CLI::error( 'Maintenance mode already activated.' );
		}

		$this->upgrader->maintenance_mode( true );
		WP_CLI::success( 'Activated Maintenance mode.' );
	}

	/**
	 * Deactivates maintenance mode.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp maintenance-mode deactivate
	 *     Disabling Maintenance mode...
	 *     Success: Deactivated Maintenance mode.
	 */
	public function deactivate() {
		if ( ! $this->get_maintenance_mode_status() ) {
			WP_CLI::error( 'Maintenance mode already deactivated.' );
		}

		$this->upgrader->maintenance_mode( false );
		WP_CLI::success( 'Deactivated Maintenance mode.' );
	}

	/**
	 * Displays maintenance mode status.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp maintenance-mode status
	 *     Maintenance mode is active.
	 */
	public function status() {
		$status = $this->get_maintenance_mode_status() ? 'active' : 'not active';
		WP_CLI::line( "Maintenance mode is {$status}." );
	}

	/**
	 * Detects maintenance mode status.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp maintenance-mode is-active
	 *     $ echo $?
	 *     1
	 *
	 * @subcommand is-active
	 */
	public function is_active() {
		WP_CLI::halt( $this->get_maintenance_mode_status() ? 0 : 1 );
	}

	/**
	 * Returns status of maintenance mode.
	 *
	 * @return bool
	 */
	private function get_maintenance_mode_status() {
		$wp_filesystem = $this->init_wp_filesystem();

		$maintenance_file = trailingslashit( $wp_filesystem->abspath() ) . '.maintenance';

		return $wp_filesystem->exists( $maintenance_file );
	}

	/**
	 * Initializes WP_Filesystem.
	 *
	 * @return WP_Filesystem_Base
	 */
	protected function init_wp_filesystem() {
		global $wp_filesystem;
		WP_Filesystem();

		return $wp_filesystem;
	}
}
