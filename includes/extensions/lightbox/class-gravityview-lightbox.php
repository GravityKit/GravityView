<?php

/**
 * Manage lightbox scripts for GravityView
 *
 * TODO: Add a global setting for lightbox providers
 * TODO: Add per-field settings
 *
 * @internal
 */
class GravityView_Lightbox {

	private static $providers = array();

	/**
	 * GravityView_Lightbox_Provider constructor.
	 */
	public function __construct() {

		require_once gravityview()->plugin->dir( 'includes/extensions/lightbox/class-gravityview-lightbox-provider.php' );
		require_once gravityview()->plugin->dir( 'includes/extensions/lightbox/fancybox/class-gravityview-lightbox-provider-fancybox.php' );
		require_once gravityview()->plugin->dir( 'includes/extensions/lightbox/featherlight/class-gravityview-lightbox-provider-featherlight.php' );

		// Using plugins_loaded instead of gravityview/loaded because Addon_Settings waits for all plugins to load.
		add_action( 'plugins_loaded', array( $this, 'setup_providers' ), 11 );
	}

	/**
	 * Activate the lightbox provider chosen in settings
	 *
	 * @internal
	 */
	public function setup_providers() {

		if( gravityview()->request->is_admin() ) {
			return;
		}

		$provider = gravityview()->plugin->settings->get( 'lightbox' );

		if ( ! isset( self::$providers[ $provider ] ) ) {
			return;
		}

		if ( ! class_exists( self::$providers[ $provider ] ) ) {
			return;
		}

		new self::$providers[ $provider ];
	}

	/**
	 * Register lightbox providers
	 *
	 * @param $provider
	 */
	public static function register( $provider ) {
		self::$providers[ $provider::$slug ] = $provider;
	}

}

new GravityView_Lightbox();
