<?php

use WP_CLI\Fetchers\Plugin;
use WP_CLI\Formatter;
use WP_CLI\ParsePluginNameInput;
use WP_CLI\Utils;

/**
 * Manages plugin auto-updates.
 *
 * ## EXAMPLES
 *
 *     # Enable the auto-updates for a plugin
 *     $ wp plugin auto-updates enable hello
 *     Plugin auto-updates for 'hello' enabled.
 *     Success: Enabled 1 of 1 plugin auto-updates.
 *
 *     # Disable the auto-updates for a plugin
 *     $ wp plugin auto-updates disable hello
 *     Plugin auto-updates for 'hello' disabled.
 *     Success: Disabled 1 of 1 plugin auto-updates.
 *
 *     # Get the status of plugin auto-updates
 *     $ wp plugin auto-updates status hello
 *     Auto-updates for plugin 'hello' are disabled.
 *
 * @package wp-cli
 */
class Plugin_AutoUpdates_Command {

	use ParsePluginNameInput;

	/**
	 * Site option that stores the status of plugin auto-updates.
	 *
	 * @var string
	 */
	const SITE_OPTION = 'auto_update_plugins';

	/**
	 * Plugin fetcher instance.
	 *
	 * @var Plugin
	 */
	protected $fetcher;

	/**
	 * Plugin_AutoUpdates_Command constructor.
	 */
	public function __construct() {
		$this->fetcher = new Plugin();
	}

	/**
	 * Enables the auto-updates for a plugin.
	 *
	 * ## OPTIONS
	 *
	 * [<plugin>...]
	 * : One or more plugins to enable auto-updates for.
	 *
	 * [--all]
	 * : If set, auto-updates will be enabled for all plugins.
	 *
	 * [--disabled-only]
	 * : If set, filters list of plugins to only include the ones that have
	 * auto-updates disabled.
	 *
	 * ## EXAMPLES
	 *
	 *     # Enable the auto-updates for a plugin
	 *     $ wp plugin auto-updates enable hello
	 *     Plugin auto-updates for 'hello' enabled.
	 *     Success: Enabled 1 of 1 plugin auto-updates.
	 */
	public function enable( $args, $assoc_args ) {
		$all           = Utils\get_flag_value( $assoc_args, 'all', false );
		$disabled_only = Utils\get_flag_value( $assoc_args, 'disabled-only', false );

		$args = $this->check_optional_args_and_all( $args, $all );
		if ( ! $args ) {
			return;
		}

		$plugins      = $this->fetcher->get_many( $args );
		$auto_updates = get_site_option( static::SITE_OPTION );

		if ( false === $auto_updates ) {
			$auto_updates = [];
		}

		$count     = 0;
		$successes = 0;

		foreach ( $plugins as $plugin ) {
			$enabled = in_array( $plugin->file, $auto_updates, true );

			if ( $disabled_only && $enabled ) {
				continue;
			}

			$count++;

			if ( $enabled ) {
				WP_CLI::warning(
					"Auto-updates already enabled for plugin {$plugin->name}."
				);
			} else {
				$auto_updates[] = $plugin->file;
				$successes++;
			}
		}

		if ( 0 === $count ) {
			WP_CLI::error(
				'No plugins provided to enable auto-updates for.'
			);
		}

		update_site_option( static::SITE_OPTION, $auto_updates );

		Utils\report_batch_operation_results(
			'plugin auto-update',
			'enable',
			$count,
			$successes,
			$count - $successes
		);
	}

	/**
	 * Disables the auto-updates for a plugin.
	 *
	 * ## OPTIONS
	 *
	 * [<plugin>...]
	 * : One or more plugins to disable auto-updates for.
	 *
	 * [--all]
	 * : If set, auto-updates will be disabled for all plugins.
	 *
	 * [--enabled-only]
	 * : If set, filters list of plugins to only include the ones that have
	 * auto-updates enabled.
	 *
	 * ## EXAMPLES
	 *
	 *     # Disable the auto-updates for a plugin
	 *     $ wp plugin auto-updates disable hello
	 *     Plugin auto-updates for 'hello' disabled.
	 *     Success: Disabled 1 of 1 plugin auto-updates.
	 */
	public function disable( $args, $assoc_args ) {
		$all          = Utils\get_flag_value( $assoc_args, 'all', false );
		$enabled_only = Utils\get_flag_value( $assoc_args, 'enabled-only', false );

		$args = $this->check_optional_args_and_all( $args, $all );
		if ( ! $args ) {
			return;
		}

		$plugins      = $this->fetcher->get_many( $args );
		$auto_updates = get_site_option( static::SITE_OPTION );

		if ( false === $auto_updates ) {
			$auto_updates = [];
		}

		$count     = 0;
		$successes = 0;

		foreach ( $plugins as $plugin ) {
			$enabled = in_array( $plugin->file, $auto_updates, true );

			if ( $enabled_only && ! $enabled ) {
				continue;
			}

			$count++;

			if ( ! $enabled ) {
				WP_CLI::warning(
					"Auto-updates already disabled for plugin {$plugin->name}."
				);
			} else {
				$auto_updates = array_diff( $auto_updates, [ $plugin->file ] );
				$successes++;
			}
		}

		if ( 0 === $count ) {
			WP_CLI::error(
				'No plugins provided to disable auto-updates for.'
			);
		}

		if ( count( $auto_updates ) > 0 ) {
			update_site_option( static::SITE_OPTION, $auto_updates );
		} else {
			delete_site_option( static::SITE_OPTION );
		}

		Utils\report_batch_operation_results(
			'plugin auto-update',
			'disable',
			$count,
			$successes,
			$count - $successes
		);
	}

	/**
	 * Shows the status of auto-updates for a plugin.
	 *
	 * ## OPTIONS
	 *
	 * [<plugin>...]
	 * : One or more plugins to show the status of the auto-updates of.
	 *
	 * [--all]
	 * : If set, the status of auto-updates for all plugins will be shown.
	 *
	 * [--enabled-only]
	 * : If set, filters list of plugins to only include the ones that have
	 * auto-updates enabled.
	 *
	 * [--disabled-only]
	 * : If set, filters list of plugins to only include the ones that have
	 * auto-updates disabled.
	 *
	 * [--field=<field>]
	 * : Only show the provided field.
	 *
	 * ## EXAMPLES
	 *
	 *     # Get the status of plugin auto-updates
	 *     $ wp plugin auto-updates status hello
	 *     +-------+----------+
	 *     | name  | status   |
	 *     +-------+----------+
	 *     | hello | disabled |
	 *     +-------+----------+
	 *
	 *     # Get the list of plugins that have auto-updates enabled
	 *     $ wp plugin auto-updates status --all --enabled-only --field=name
	 *     akismet
	 *     duplicate-post
	 */
	public function status( $args, $assoc_args ) {
		$all           = Utils\get_flag_value( $assoc_args, 'all', false );
		$enabled_only  = Utils\get_flag_value( $assoc_args, 'enabled-only', false );
		$disabled_only = Utils\get_flag_value( $assoc_args, 'disabled-only', false );

		if ( $enabled_only && $disabled_only ) {
			WP_CLI::error(
				'--enabled-only and --disabled-only are mutually exclusive and '
				. 'cannot be used at the same time.'
			);
		}

		$args = $this->check_optional_args_and_all( $args, $all );
		if ( ! $args ) {
			return;
		}

		$plugins      = $this->fetcher->get_many( $args );
		$auto_updates = get_site_option( static::SITE_OPTION );

		if ( false === $auto_updates ) {
			$auto_updates = [];
		}

		$results = [];

		foreach ( $plugins as $plugin ) {
			$enabled = in_array( $plugin->file, $auto_updates, true );

			if ( $enabled_only && ! $enabled ) {
				continue;
			}

			if ( $disabled_only && $enabled ) {
				continue;
			}

			$results[] = [
				'name'   => $plugin->name,
				'file'   => $plugin->file,
				'status' => $enabled ? 'enabled' : 'disabled',
			];
		}

		$formatter = new Formatter( $assoc_args, [ 'name', 'status' ], 'plugin' );
		$formatter->display_items( $results );
	}
}
