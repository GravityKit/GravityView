<?php
/**
 * GravityView Beaver Builder Integration
 *
 * @package GravityKit\GravityView\Extensions\BeaverBuilder
 * @since TODO
 */

namespace GravityKit\GravityView\Extensions\BeaverBuilder;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * Main Beaver Builder integration class for GravityView.
 *
 * Provides basic Beaver Builder module functionality for embedding GravityView Views.
 *
 * @since TODO
 */
class Integration {

	/**
	 * Initialize the Beaver Builder integration.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	public function __construct() {
		// Check if Beaver Builder is available.
		if ( ! $this->is_beaver_builder_active() ) {
			return;
		}

		// Register the module when Beaver Builder is ready.
		add_action( 'init', [ $this, 'register_modules' ], 20 );
	}

	/**
	 * Check if Beaver Builder is active.
	 *
	 * @since TODO
	 *
	 * @return bool Whether Beaver Builder is available.
	 */
	private function is_beaver_builder_active() {
		// Check for Beaver Builder plugin.
		if ( defined( 'FL_BUILDER_VERSION' ) ) {
			return true;
		}

		// Check for FLBuilder class.
		if ( class_exists( 'FLBuilder' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Register GravityView module with Beaver Builder.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	public function register_modules() {
		if ( ! class_exists( 'FLBuilderModule' ) ) {
			return;
		}

		require_once __DIR__ . '/class-basic-module.php';
	}
}

// Initialize the Beaver Builder integration.
new Integration();
