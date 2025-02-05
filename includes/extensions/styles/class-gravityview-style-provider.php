<?php

/**
 * Registers a style provider.
 *
 * @internal Currently internal; not ready for public usage.
 */
abstract class GravityView_Style_Provider {

	/**
	 * The name of the provider, as displayed in the admin.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The slug of the style, as used in the View settings.
	 *
	 * @var string
	 */
	public static $slug;

	/**
	 * The asset slug, as used when registering the style with WordPress.
	 *
	 * @var string
	 */
	public static $style_slug;

	/**
	 * The CSS file name, as saved in the includes/extensions/styles/css/ directory.
	 *
	 * @var string
	 */
	public static $css_file_name;

	/**
	 * Override this method to set the provider name.
	 */
	abstract function __construct();

	/**
	 * Adds actions and that modify GravityView to use this lightbox provider
	 */
	public function add_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'gravityview/template/before', array( $this, 'print_assets' ) );
	}

	/**
	 * Returns the provider name string, as set in the constructor.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Prints scripts for lightbox after a View is rendered
	 *
	 * @since 2.10.1
	 *
	 * @param GV\Template_Context $gravityview
	 *
	 * @return void
	 */
	public function print_assets( $gravityview ) {

		if ( ! self::is_active( $gravityview ) ) {
			return;
		}

		$stylesheet = $gravityview->view->settings->get( 'stylesheet', '' );

		if ( empty( $stylesheet ) ) {
			return;
		}

		wp_print_styles( static::$style_slug );
	}

	/**
	 * Returns whether the provider is active for this View
	 *
	 * @since 2.10.1
	 *
	 * @param GV\Template_Context $gravityview
	 *
	 * @return bool true: yes! false: no!
	 */
	protected static function is_active( $gravityview ) {

		$stylesheet = $gravityview->view->settings->get( 'stylesheet', '' );

		if ( empty( $stylesheet ) ) {
			return false;
		}

		if ( static::$slug !== $stylesheet ) {
			return false;
		}

		return true;
	}

	/**
	 * Removes actions that were added by {@see GravityView_Lightbox_Provider::add_hooks}
	 *
	 * @internal Do not call directly. Instead, use:
	 *
	 * <code>
	 * do_action( 'gravityview/lightbox/provider', 'slug' );
	 * </code>
	 */
	public function remove_hooks() {
		remove_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Get default settings for the script
	 *
	 * @return array
	 */
	protected function default_settings() {
		return array();
	}

	/**
	 * Output raw HTML in the wp_footer()
	 *
	 * @internal
	 */
	public function output_footer() {}

	/**
	 * Enqueue styles for the lightbox
	 *
	 * @internal
	 */
	public function enqueue_styles() {
		wp_register_style( static::$style_slug, plugins_url( 'includes/extensions/styles/css/' . static::$css_file_name, GRAVITYVIEW_FILE ), [], GV_PLUGIN_VERSION );
	}

}
