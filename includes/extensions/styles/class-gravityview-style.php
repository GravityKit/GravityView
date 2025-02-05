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

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts and styles for the admin
	 *
	 * @internal
	 */
	public function admin_enqueue_scripts() {

		// Only enqueue on GravityView View editor
		if( ! gravityview()->request->is_admin() ) {
			return;
		}

		// Enqueue the admin styles
		wp_enqueue_style( 'gravityview-admin-view-editor-styles', plugins_url( 'css/admin.css', __FILE__ ), array(), GravityView_Plugin::version );
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
