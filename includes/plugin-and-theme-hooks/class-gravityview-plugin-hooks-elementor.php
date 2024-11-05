<?php
/**
 * Add Elegant Themes compatibility to GravityView (Divi theme)
 *
 * @file      class-gravityview-theme-hooks-elegant-themes.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2016', Katz Web Services, Inc.
 *
 * @since 1.17.2
 */

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Widget_Base;

/**
 * @inheritDoc
 * @since 1.17.2
 */
class GravityView_Theme_Hooks_Elementor extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.17.2
	 */
	protected $constant_name = 'ELEMENTOR_VERSION';

	protected $content_meta_keys = array( '_elementor_data' );

	/**
	 * GravityView_Theme_Hooks_Elementor constructor.
	 */
	public function add_hooks() {
		parent::add_hooks();

		add_action( 'elementor/widgets/register', [ $this, 'register_elementor_widget' ] );
	}

	/**
	 * Register the Gravity Forms widget for Elementor
	 *
	 * @since 1.17.2
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_elementor_widget( $widgets_manager ) {

		// Include Widget file.
		require( plugin_dir_path( __FILE__ ) . 'class-gravityview-plugin-hooks-elementor-widget.php' );

		// Register widget
		$widgets_manager->register( new GravityView_Elementor_Widget() );

		add_action( 'elementor/editor/after_enqueue_styles', [
			$this,
			'enqueue_editor_styles',
		] );
	}

	public function enqueue_editor_styles() {
		wp_add_inline_style(
			'elementor-editor',
			self::get_custom_icon_style()
		);
	}

	/**
	 * Get custom icon style.
	 *
	 * @return string Custom icon CSS.
	 */
	private static function get_custom_icon_style() {
		$icon_svg         = GravityView_Elementor_Widget::get_custom_icon();
		$icon_svg_encoded = str_replace( '"', "'", $icon_svg );
		$icon_svg_url     = 'data:image/svg+xml;utf8,' . rawurlencode( $icon_svg_encoded );

		return ".elementor-element .icon .gk-gravityview-icon {
            width: 52px;
            height: 52px;
            display: inline-block;
            margin-top: -12px; /* Allow icon to be taller */
            background-image: url('{$icon_svg_url}');
        }";
	}

}

new GravityView_Theme_Hooks_Elementor();
