<?php

use WP_CLI\Fetchers\Theme;
use WP_CLI\Formatter;
use WP_CLI\ParseThemeNameInput;
use WP_CLI\Utils;

/**
 * Manages theme auto-updates.
 *
 * ## EXAMPLES
 *
 *     # Enable the auto-updates for a theme
 *     $ wp theme auto-updates enable twentysixteen
 *     Theme auto-updates for 'twentysixteen' enabled.
 *     Success: Enabled 1 of 1 theme auto-updates.
 *
 *     # Disable the auto-updates for a theme
 *     $ wp theme auto-updates disable twentysixteen
 *     Theme auto-updates for 'twentysixteen' disabled.
 *     Success: Disabled 1 of 1 theme auto-updates.
 *
 *     # Get the status of theme auto-updates
 *     $ wp theme auto-updates status twentysixteen
 *     Auto-updates for theme 'twentysixteen' are disabled.
 *
 * @package wp-cli
 */
class Theme_AutoUpdates_Command {

	use ParseThemeNameInput;

	/**
	 * Site option that stores the status of theme auto-updates.
	 *
	 * @var string
	 */
	const SITE_OPTION = 'auto_update_themes';

	/**
	 * Theme fetcher instance.
	 *
	 * @var Theme
	 */
	protected $fetcher;

	/**
	 * Theme_AutoUpdates_Command constructor.
	 */
	public function __construct() {
		$this->fetcher = new Theme();
	}

	/**
	 * Enables the auto-updates for a theme.
	 *
	 * ## OPTIONS
	 *
	 * [<theme>...]
	 * : One or more themes to enable auto-updates for.
	 *
	 * [--all]
	 * : If set, auto-updates will be enabled for all themes.
	 *
	 * [--disabled-only]
	 * : If set, filters list of themes to only include the ones that have
	 * auto-updates disabled.
	 *
	 * ## EXAMPLES
	 *
	 *     # Enable the auto-updates for a theme
	 *     $ wp theme auto-updates enable twentysixteen
	 *     Theme auto-updates for 'twentysixteen' enabled.
	 *     Success: Enabled 1 of 1 theme auto-updates.
	 */
	public function enable( $args, $assoc_args ) {
		$all           = Utils\get_flag_value( $assoc_args, 'all', false );
		$disabled_only = Utils\get_flag_value( $assoc_args, 'disabled-only', false );

		$args = $this->check_optional_args_and_all( $args, $all );
		if ( ! $args ) {
			return;
		}

		$themes       = $this->fetcher->get_many( $args );
		$auto_updates = get_site_option( static::SITE_OPTION );

		if ( false === $auto_updates ) {
			$auto_updates = [];
		}

		$count     = 0;
		$successes = 0;

		foreach ( $themes as $theme ) {
			$enabled = in_array( $theme->stylesheet, $auto_updates, true );

			if ( $disabled_only && $enabled ) {
				continue;
			}

			$count++;

			if ( $enabled ) {
				WP_CLI::warning(
					"Auto-updates already enabled for theme {$theme->stylesheet}."
				);
			} else {
				$auto_updates[] = $theme->stylesheet;
				$successes++;
			}
		}

		if ( 0 === $count ) {
			WP_CLI::error(
				'No themes provided to enable auto-updates for.'
			);
		}

		update_site_option( static::SITE_OPTION, $auto_updates );

		Utils\report_batch_operation_results(
			'theme auto-update',
			'enable',
			$count,
			$successes,
			$count - $successes
		);
	}

	/**
	 * Disables the auto-updates for a theme.
	 *
	 * ## OPTIONS
	 *
	 * [<theme>...]
	 * : One or more themes to disable auto-updates for.
	 *
	 * [--all]
	 * : If set, auto-updates will be disabled for all themes.
	 *
	 * [--enabled-only]
	 * : If set, filters list of themes to only include the ones that have
	 * auto-updates enabled.
	 *
	 * ## EXAMPLES
	 *
	 *     # Disable the auto-updates for a theme
	 *     $ wp theme auto-updates disable twentysixteen
	 *     Theme auto-updates for 'twentysixteen' disabled.
	 *     Success: Disabled 1 of 1 theme auto-updates.
	 */
	public function disable( $args, $assoc_args ) {
		$all          = Utils\get_flag_value( $assoc_args, 'all', false );
		$enabled_only = Utils\get_flag_value( $assoc_args, 'enabled-only', false );

		$args = $this->check_optional_args_and_all( $args, $all );
		if ( ! $args ) {
			return;
		}

		$themes       = $this->fetcher->get_many( $args );
		$auto_updates = get_site_option( static::SITE_OPTION );

		if ( false === $auto_updates ) {
			$auto_updates = [];
		}

		$count     = 0;
		$successes = 0;

		foreach ( $themes as $theme ) {
			$enabled = in_array( $theme->stylesheet, $auto_updates, true );

			if ( $enabled_only && ! $enabled ) {
				continue;
			}

			$count++;

			if ( ! $enabled ) {
				WP_CLI::warning(
					"Auto-updates already disabled for theme {$theme->stylesheet}."
				);
			} else {
				$auto_updates = array_diff( $auto_updates, [ $theme->stylesheet ] );
				$successes++;
			}
		}

		if ( 0 === $count ) {
			WP_CLI::error(
				'No themes provided to disable auto-updates for.'
			);
		}

		if ( count( $auto_updates ) > 0 ) {
			update_site_option( static::SITE_OPTION, $auto_updates );
		} else {
			delete_site_option( static::SITE_OPTION );
		}

		Utils\report_batch_operation_results(
			'theme auto-update',
			'disable',
			$count,
			$successes,
			$count - $successes
		);
	}

	/**
	 * Shows the status of auto-updates for a theme.
	 *
	 * ## OPTIONS
	 *
	 * [<theme>...]
	 * : One or more themes to show the status of the auto-updates of.
	 *
	 * [--all]
	 * : If set, the status of auto-updates for all themes will be shown.
	 *
	 * [--enabled-only]
	 * : If set, filters list of themes to only include the ones that have
	 * auto-updates enabled.
	 *
	 * [--disabled-only]
	 * : If set, filters list of themes to only include the ones that have
	 * auto-updates disabled.
	 *
	 * [--field=<field>]
	 * : Only show the provided field.
	 *
	 * ## EXAMPLES
	 *
	 *     # Get the status of theme auto-updates
	 *     $ wp theme auto-updates status twentysixteen
	 *     +---------------+----------+
	 *     | name          | status   |
	 *     +---------------+----------+
	 *     | twentysixteen | disabled |
	 *     +---------------+----------+
	 *
	 *     # Get the list of themes that have auto-updates enabled
	 *     $ wp theme auto-updates status --all --enabled-only --field=name
	 *     twentysixteen
	 *     twentyseventeen
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

		$themes       = $this->fetcher->get_many( $args );
		$auto_updates = get_site_option( static::SITE_OPTION );

		if ( false === $auto_updates ) {
			$auto_updates = [];
		}

		$results = [];

		foreach ( $themes as $theme ) {
			$enabled = in_array( $theme->stylesheet, $auto_updates, true );

			if ( $enabled_only && ! $enabled ) {
				continue;
			}

			if ( $disabled_only && $enabled ) {
				continue;
			}

			$results[] = [
				'name'   => $theme->stylesheet,
				'name
			'            => $theme->stylesheet,
				'status' => $enabled ? 'enabled' : 'disabled',
			];
		}

		$formatter = new Formatter( $assoc_args, [ 'name', 'status' ], 'theme' );
		$formatter->display_items( $results );
	}
}
