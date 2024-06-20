<?php

/**
 * Registers a style provider.
 *
 * @internal Currently internal; not ready for public usage.
 */
abstract class GravityView_Style_Provider {

	public static $slug;

	#public static $script_slug;

	public static $style_slug;


	public static $css_file_name;

	/**
	 * Adds actions and that modify GravityView to use this lightbox provider
	 */
	public function add_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'gravityview/template/before', array( $this, 'print_assets' ) );
		#add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		#add_action( 'wp_footer', array( $this, 'output_footer' ) );
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

		echo '<link rel="stylesheet" href="' . esc_url( plugins_url( 'includes/extensions/styles/css/' . static::$slug . '.min.css', GRAVITYVIEW_FILE ) ) . '" />';

		#wp_print_scripts( static::$script_slug );
		#wp_print_styles( static::$style_slug );
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
		wp_register_style( self::$style_slug, plugins_url( 'includes/extensions/styles/css/' . self::$css_file_name, GRAVITYVIEW_FILE ), [], GV_PLUGIN_VERSION );
	}

}
