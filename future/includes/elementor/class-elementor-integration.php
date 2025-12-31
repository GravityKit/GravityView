<?php
/**
 * GravityView Elementor Integration
 *
 * @package GravityKit\GravityView\Extensions\Elementor
 * @since TODO
 */

namespace GravityKit\GravityView\Extensions\Elementor;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * Main Elementor integration class for GravityView.
 *
 * Provides basic Elementor widget functionality for embedding GravityView Views.
 * The Advanced Elementor Widget plugin extends this basic functionality.
 *
 * @since TODO
 */
class Integration {

	/**
	 * Minimum Elementor version required.
	 *
	 * @since TODO
	 */
	const MIN_ELEMENTOR_VERSION = '3.0.0';

	/**
	 * Initialize the Elementor integration.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	public function __construct() {
		// Check if Elementor is loaded.
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		// Check minimum Elementor version.
		if ( ! version_compare( ELEMENTOR_VERSION, self::MIN_ELEMENTOR_VERSION, '>=' ) ) {
			return;
		}

		// Register widget at priority 10 (Advanced Widget will override at priority 15).
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ], 10 );

		// Register widget icon styles.
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'register_icon_styles' ] );
	}

	/**
	 * Register GravityView widget with Elementor.
	 *
	 * @since TODO
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager The Elementor widgets manager.
	 *
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		require_once __DIR__ . '/class-basic-widget.php';
		$widgets_manager->register( new Basic_Widget() );
	}

	/**
	 * Register widget icon styles for the GravityView widget.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	public function register_icon_styles() {
		$icon_svg     = $this->get_custom_icon();
		$icon_svg_url = 'data:image/svg+xml;utf8,' . rawurlencode( $icon_svg );

		$css = "
		.elementor-element .icon .gk-gravityview-icon {
			display: block;
			height: 28px;
			width: 30.5px;
			margin: 0 auto;
			background-color: currentColor;
			-webkit-mask-image: url('{$icon_svg_url}');
			mask-image: url('{$icon_svg_url}');
			-webkit-mask-repeat: no-repeat;
			mask-repeat: no-repeat;
			-webkit-mask-position: center;
			mask-position: center;
			-webkit-mask-size: contain;
			mask-size: contain;
		}
		";

		wp_add_inline_style( 'elementor-editor', $css );
	}

	/**
	 * Get custom SVG icon for the GravityView widget.
	 *
	 * @since TODO
	 *
	 * @return string SVG icon markup.
	 */
	private function get_custom_icon() {
		return '<svg width="30.5" height="28" viewBox="0 0 48 44" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M42.4105 36.6666H36.8824V38.4999C36.8824 41.5375 34.3902 43.9999 31.3544 43.9999H16.6134C13.5375 43.9999 11.0853 41.5375 11.0853 38.4999V11H5.55724C4.51437 11 3.71481 11.8207 3.71481 12.8333V31.1666C3.71481 32.1792 4.51437 33 5.55724 33H6.47884C6.96291 33 7.39967 33.4104 7.39967 33.9167V35.75C7.39967 36.2562 6.96291 36.6666 6.47884 36.6666H5.55724C2.47856 36.6666 0.0291748 34.2042 0.0291748 31.1666V12.8333C0.0291748 9.79573 2.47856 7.33326 5.55724 7.33326H11.0853V5.5C11.0853 2.4624 13.5375 0 16.6134 0H31.3544C34.3902 0 36.8824 2.4624 36.8824 5.5V33H42.4105C43.4132 33 44.2529 32.1792 44.2529 31.1666V12.8333C44.2529 11.8207 43.4132 11 42.4105 11H41.4889C40.9647 11 40.5673 10.5896 40.5673 10.0833V8.24992C40.5673 7.74369 40.9647 7.33326 41.4889 7.33326H42.4105C45.449 7.33326 47.9378 9.79573 47.9378 12.8333V31.1666C47.9378 34.2042 45.449 36.6666 42.4105 36.6666ZM33.1968 5.5C33.1968 4.48739 32.3544 3.66667 31.3544 3.66667H16.6134C15.5733 3.66667 14.7709 4.48739 14.7709 5.5V38.4999C14.7709 39.5125 15.5733 40.3333 16.6134 40.3333H31.3544C32.3544 40.3333 33.1968 39.5125 33.1968 38.4999V5.5Z"/></svg>';
	}
}

// Initialize the Elementor integration.
new Integration();
