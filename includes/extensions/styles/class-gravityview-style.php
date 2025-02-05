<?php

/**
 * Manage styles for GravityView.
 *
 * @internal
 */
class GravityView_Style {
	const DEFAULT_PROVIDER = '';

	/**
	 * @var array Array of available providers
	 */
	private static $providers = [];

	/**
	 * GravityView_Lightbox_Provider constructor.
	 */
	public function __construct() {
		require_once gravityview()->plugin->dir( 'includes/extensions/styles/class-gravityview-style-provider.php' );

		foreach ( glob( gravityview()->plugin->dir( 'includes/extensions/styles/class-gravityview-style-provider*.php' ) ) as $style_provider ) {
			include_once $style_provider;
		}

		add_action( 'plugins_loaded', [ $this, 'set_provider' ], 11 );

		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

		add_action( 'gravityview/template/before', [ $this, 'setup_grid' ] );
	}

	function setup_grid( $gravityview ) {
		$view = $gravityview->view;
		$is_grid_enabled = $view->settings->get( 'grid' );

		if( ! $is_grid_enabled ) {
			return;
		}

		$grid_gap = (int) $view->settings->get( 'grid_gap', 20 );
		$grid_columns = (int) $view->settings->get( 'grid_columns', 2 );


		// Create CSS to make GravityView listings into a grid
		$css = strtr( '
			.gv-layout-builder-container.gv-container-[view_id],
			.gv-list-multiple-container.gv-container-[view_id] {
			    display: grid;
				grid-template-columns: repeat( [columns], 1fr );
				grid-gap: [gap]px;
			}
		', [
			'[view_id]'  => $view->ID,
			'[columns]' => $grid_columns,
			'[gap]'     => $grid_gap,
		] );
		wp_add_inline_style( 'gravityview_default_style', $css );
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
		wp_enqueue_style( 'gravityview-admin-view-editor-styles', plugins_url( 'css/admin.css', __FILE__ ), [], GravityView_Plugin::version );
	}

	/**
	 * Activate the lightbox provider chosen in settings
	 *
	 * @internal
	 */
	public function set_provider() {
		foreach( self::$providers as $provider ) {
			$provider::add_hooks();
		}
	}

	/**
	 * Register style providers with key as the slug and value as the class name.
	 *
	 * We're only registering the class name here, not the instance; we can instantiate the class when we need it.
	 *
	 * @param $provider
	 */
	public static function register( $provider ) {
		self::$providers[ $provider::$slug ] = $provider;
	}

	/**
	 * Returns an array of available styles with the slug as the key and the name as the value.
	 *
	 * @return []
	 */
	public static function get_styles() {
		foreach( self::$providers as $provider ) {
			$provider = new $provider();
			$styles[ $provider::$slug ] = $provider->get_name();
		}

		return $styles;
	}
}

new GravityView_Style;
