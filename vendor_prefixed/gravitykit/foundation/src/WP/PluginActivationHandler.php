<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\WP;

/**
 * This class is responsible for handling plugin activation and deactivation hooks.
 */
class PluginActivationHandler {
	const DB_OPTION_NAME = '_gk_foundation_plugin_activations';

	/**
	 * Registers activation and deactivation hooks for the plugin.
	 *
	 * Note: this method should not be called inside a hook such as `init`, `plugins_loaded`, etc.
	 *
	 * @see   https://developer.wordpress.org/reference/functions/register_activation_hook/#more-information
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_file The filename of the plugin including the path.
	 *
	 * @return void
	 */
	public function register_hooks( $plugin_file ) {
		$this->register_activation_hook( $plugin_file );
		$this->register_deactivation_hook( $plugin_file );
	}

	/**
	 * Registers activation hook for the plugin.
	 *
	 * Note: this method should not be called inside a hook such as `init`, `plugins_loaded`, etc.
	 *
	 * @see   https://developer.wordpress.org/reference/functions/register_activation_hook/#more-information
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_file The filename of the plugin including the path.
	 *
	 * @return void
	 */
	public function register_activation_hook( $plugin_file ) {
		if ( has_action( 'activate_' . $plugin_file ) ) {
			return;
		}

		$callback = function () use ( $plugin_file ) {
			$plugin_activations = $this->get_plugin_activations();

			if ( ! in_array( $plugin_file, $plugin_activations, true ) ) {
				$plugin_activations[] = $plugin_file;

				$this->save_plugin_activations( $plugin_activations );
			}
		};

		register_activation_hook( $plugin_file, $callback );
	}

	/**
	 * Registers deactivation hook for the plugin.
	 *
	 * Note: this method should not be called inside a hook such as `init`, `plugins_loaded`, etc.
	 *
	 * @see   https://developer.wordpress.org/reference/functions/register_activation_hook/#more-information
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_file The filename of the plugin including the path.
	 *
	 * @return void
	 */
	public function register_deactivation_hook( $plugin_file ) {
		if ( has_action( 'deactivate_' . $plugin_file ) ) {
			return;
		}

		$callback = function () use ( $plugin_file ) {
			do_action( 'gk/foundation/plugin_deactivated', $plugin_file );
		};

		register_deactivation_hook( $plugin_file, $callback );
	}

	/**
	 * Saves activated plugins.
	 *
	 * @since 1.0.0
	 *
	 * @param array $plugin_activations Activated plugins.
	 *
	 * @return void
	 */
	public function save_plugin_activations( $plugin_activations ) {
		if ( ! empty( $plugin_activations ) ) {
			update_option( self::DB_OPTION_NAME, $plugin_activations );
		} else {
			delete_option( self::DB_OPTION_NAME );
		}
	}

	/**
	 * Returns activated plugins.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_plugin_activations() {
		return get_option( self::DB_OPTION_NAME, [] );
	}

	/**
	 * Runs on plugin activation.
	 *
	 * This method can be called inside `init` and other WP hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function fire_activation_hook() {
		$plugin_activations = $this->get_plugin_activations();

		foreach ( $plugin_activations as $i => $plugin_file ) {
			do_action( 'gk/foundation/plugin_activated', $plugin_file );

			unset( $plugin_activations[ $i ] );
		}

		$this->save_plugin_activations( $plugin_activations );
	}
}
