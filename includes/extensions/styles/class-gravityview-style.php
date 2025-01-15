<?php

/**
 * Manage styles for GravityView.
 *
 * @internal
 */
class GravityView_Style {
	const DEFAULT_PROVIDER = '';

	private static $providers = array();

	private static $active_provider = null;

	/**
	 * GravityView_Lightbox_Provider constructor.
	 */
	public function __construct() {
		require_once gravityview()->plugin->dir( 'includes/extensions/styles/class-gravityview-style-provider.php' );

		foreach ( glob( gravityview()->plugin->dir( 'includes/extensions/styles/class-gravityview-style-provider*.php' ) ) as $style_provider ) {
			include_once $style_provider;
		}

		add_action( 'plugins_loaded', array( $this, 'set_provider' ), 11 );
	}

	/**
	 * Activate the lightbox provider chosen in settings
	 *
	 * @internal
	 */
	public function set_provider() {
		foreach( self::$providers as $key => $provider ) {
			self::$active_provider = new $provider();
			self::$active_provider->add_hooks();
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

new GravityView_Style;
