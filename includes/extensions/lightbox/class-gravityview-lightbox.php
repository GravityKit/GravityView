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

	private static $active_provider = null;

	/**
	 * GravityView_Lightbox_Provider constructor.
	 */
	public function __construct() {

		require_once gravityview()->plugin->dir( 'includes/extensions/lightbox/class-gravityview-lightbox-provider.php' );
		require_once gravityview()->plugin->dir( 'includes/extensions/lightbox/fancybox/class-gravityview-lightbox-provider-fancybox.php' );

		// Using plugins_loaded instead of gravityview/loaded because Addon_Settings waits for all plugins to load.
		add_action( 'plugins_loaded', array( $this, 'set_provider' ), 11 );

		add_action( 'gravityview/lightbox/provider', array( $this, 'set_provider' ) );
	}

	/**
	 * Activate the lightbox provider chosen in settings
	 *
	 * @param string|null $provider GravityView_Lightbox_Provider::$slug of provider
	 *
	 * @internal
	 *
	 */
	public function set_provider( $provider = null ) {

		if( gravityview()->request->is_admin() ) {
			return;
		}

		if ( empty( $provider ) ) {
			$provider = gravityview()->plugin->settings->get( 'lightbox' );
		}

		if ( empty( self::$providers[ $provider ] ) || ! class_exists( self::$providers[ $provider ] ) ) {
			gravityview()->log->error( 'Lightbox provider {provider} not registered.', array( 'provider' => $provider ) );
			return;
		}

		// Already set up.
		if ( self::$active_provider && self::$active_provider instanceof self::$providers[ $provider ] ) {
			return;
		}

		// We're switching providers; remove the hooks that were added.
		if( self::$active_provider ) {
			self::$active_provider->remove_hooks();
		}

		self::$active_provider = new self::$providers[ $provider ];

		self::$active_provider->add_hooks();
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
