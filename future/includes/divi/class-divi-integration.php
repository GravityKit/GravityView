<?php
/**
 * GravityView Divi Integration
 *
 * @package GravityKit\GravityView\Extensions\Divi
 * @since TODO
 */

namespace GravityKit\GravityView\Extensions\Divi;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * Main Divi integration class for GravityView.
 *
 * Provides basic Divi Builder module functionality for embedding GravityView Views.
 *
 * @since TODO
 */
class Integration {

	/**
	 * Initialize the Divi integration.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	public function __construct() {
		// Check if Divi Builder is available.
		if ( ! $this->is_divi_active() ) {
			return;
		}

		// Register the module when Divi is ready.
		add_action( 'et_builder_ready', [ $this, 'register_modules' ] );
	}

	/**
	 * Check if Divi Builder is active.
	 *
	 * @since TODO
	 *
	 * @return bool Whether Divi Builder is available.
	 */
	private function is_divi_active() {
		// Check for Divi theme.
		$theme = wp_get_theme();
		if ( 'Divi' === $theme->get( 'Name' ) || 'Divi' === $theme->parent_theme ) {
			return true;
		}

		// Check for Divi Builder plugin.
		if ( defined( 'ET_BUILDER_PLUGIN_VERSION' ) ) {
			return true;
		}

		// Check if ET_Builder_Module class exists (core Divi class).
		if ( class_exists( 'ET_Builder_Module' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Register GravityView module with Divi Builder.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	public function register_modules() {
		if ( ! class_exists( 'ET_Builder_Module' ) ) {
			return;
		}

		require_once __DIR__ . '/class-basic-module.php';

		// The module registers itself in its constructor.
		new Basic_Module();
	}
}

// Initialize the Divi integration.
new Integration();
