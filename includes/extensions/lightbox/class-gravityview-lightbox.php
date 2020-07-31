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

		add_action( 'gravityview/loaded', array( $this, 'setup_providers' ) );
	}

	/**
	 * Instantiates the registered lightbox providers
	 *
	 * @internal
	 */
	public function setup_providers() {

		foreach ( self::$providers as $provider ) {

			if ( ! class_exists( $provider ) ) {
				continue;
			}

			new $provider;
		}
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
