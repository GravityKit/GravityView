<?php

namespace WP_CLI;

use WP_CLI;

trait ParsePluginNameInput {

	/**
	 * If have optional args ([<plugin>...]) and an all option, then check have something to do.
	 *
	 * @param array  $args Passed-in arguments.
	 * @param bool   $all All flag.
	 * @param string $verb Optional. Verb to use. Defaults to 'install'.
	 * @return array Same as $args if not all, otherwise all slugs.
	 * @throws ExitException If neither plugin name nor --all were provided.
	 */
	protected function check_optional_args_and_all( $args, $all, $verb = 'install' ) {
		if ( $all ) {
			$args = array_map(
				'\WP_CLI\Utils\get_plugin_name',
				array_keys( $this->get_all_plugins() )
			);
		}

		if ( empty( $args ) ) {
			if ( ! $all ) {
				WP_CLI::error( 'Please specify one or more plugins, or use --all.' );
			}

			$past_tense_verb = Utils\past_tense_verb( $verb );
			WP_CLI::success( "No plugins {$past_tense_verb}." ); // Don't error if --all given for BC.
		}

		return $args;
	}

	/**
	 * Gets all available plugins.
	 *
	 * Uses the same filter core uses in plugins.php to determine which plugins
	 * should be available to manage through the WP_Plugins_List_Table class.
	 *
	 * @return array
	 */
	private function get_all_plugins() {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Calling native WordPress hook.
		return apply_filters( 'all_plugins', get_plugins() );
	}
}
