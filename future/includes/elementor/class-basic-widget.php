<?php
/**
 * GravityView Basic Widget for Elementor
 *
 * @package GravityKit\GravityView\Extensions\Elementor
 * @since TODO
 */

namespace GravityKit\GravityView\Extensions\Elementor;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use GravityKit\GravityView\Gutenberg\Blocks;
use GVCommon;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * GravityView Basic Widget for Elementor.
 *
 * Provides basic functionality for embedding GravityView Views in Elementor.
 * Can be extended by the Advanced Elementor Widget plugin for more features.
 *
 * @since TODO
 */
class Basic_Widget extends Widget_Base {

	/**
	 * Widget type identifier.
	 *
	 * CRITICAL: Must match Advanced Widget's identifier for compatibility.
	 *
	 * @since TODO
	 */
	const WIDGET_TYPE = 'gk_elementor_gravityview';

	/**
	 * Get widget name.
	 *
	 * @since TODO
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return self::WIDGET_TYPE;
	}

	/**
	 * Get widget title.
	 *
	 * @since TODO
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'GravityView', 'gk-gravityview' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since TODO
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'gk-gravityview-icon';
	}

	/**
	 * Get widget categories.
	 *
	 * @since TODO
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'basic' ];
	}

	/**
	 * Get widget keywords.
	 *
	 * @since TODO
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return [
			'gravity forms',
			'gravityforms',
			'gravityview',
			'view',
			'gravity view',
		];
	}

	/**
	 * Check if widget has dynamic content.
	 *
	 * @since TODO
	 *
	 * @return bool Whether widget has dynamic content.
	 */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/**
	 * Register widget controls.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	protected function register_controls() {

		if ( ! class_exists( '\GV\View' ) ) {
			$this->register_gravityview_not_active_notice();
			return;
		}

		$views_list = $this->get_views_list();

		if ( empty( $views_list ) || 1 === count( $views_list ) ) {
			$this->register_no_views_notice();
			return;
		}

		$this->register_view_selection_section( $views_list );
		$this->register_basic_settings_section();
	}

	/**
	 * Register a notice when GravityView is not active.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	private function register_gravityview_not_active_notice() {
		$this->start_controls_section(
			'view_notice_section',
			[
				'label' => esc_html__( 'GravityView Not Activated', 'gk-gravityview' ),
			]
		);

		$message = sprintf(
			'%s<br><br><a href="%s">%s</a>',
			esc_html__( 'GravityView is required to use this widget.', 'gk-gravityview' ),
			esc_url( admin_url( 'plugins.php' ) ),
			esc_html__( 'Activate GravityView', 'gk-gravityview' )
		);

		$this->add_control(
			'view_notice',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => $message,
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register a notice when no Views are found.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	private function register_no_views_notice() {
		$this->start_controls_section(
			'view_section',
			[
				'label' => esc_html__( 'No Views Found', 'gk-gravityview' ),
			]
		);

		$message = sprintf(
			'<p>%s <a href="%s">%s</a></p>',
			esc_html__( 'No published Views found.', 'gk-gravityview' ),
			esc_url( admin_url( 'post-new.php?post_type=gravityview' ) ),
			esc_html__( 'Create a new View', 'gk-gravityview' )
		);

		$this->add_control(
			'view_notice',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => $message,
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register View selection section.
	 *
	 * @since TODO
	 *
	 * @param array $views_list List of Views with ID as key and title as value.
	 *
	 * @return void
	 */
	private function register_view_selection_section( $views_list ) {
		$this->start_controls_section(
			'view_section',
			[
				'label' => esc_html__( 'Select a View', 'gk-gravityview' ),
			]
		);

		$this->add_control(
			'embedded_view',
			[
				'label'       => esc_html__( 'Select View', 'gk-gravityview' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $views_list,
				'default'     => '0',
				'label_block' => true,
				'description' => esc_html__( 'Choose an existing View to display on this page.', 'gk-gravityview' ),
			]
		);

		// Hidden controls for layout data (required for Advanced Widget compatibility).
		$this->add_control(
			'views_layouts',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => wp_json_encode( $this->get_views_layouts_data() ),
			]
		);

		$this->add_control(
			'layout_single',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => '',
			]
		);

		$this->add_control(
			'layout_multiple',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => '',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register basic settings section.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	private function register_basic_settings_section() {
		$this->start_controls_section(
			'view_settings_section',
			[
				'label' => esc_html__( 'View Settings', 'gk-gravityview' ),
			]
		);

		$defaults = \GV\View_Settings::defaults( true );

		// Add a few essential settings for better UX and Advanced Widget compatibility.
		$basic_settings = [ 'page_size', 'sort_field', 'sort_direction' ];

		foreach ( $basic_settings as $key ) {
			if ( ! isset( $defaults[ $key ] ) ) {
				continue;
			}

			$setting = $defaults[ $key ];

			if ( empty( $setting['show_in_shortcode'] ) ) {
				continue;
			}

			$control_config = [
				'label'       => esc_html( \GV\Utils::get( $setting, 'label', '' ) ),
				'default'     => \GV\Utils::get( $setting, 'value', '' ),
				'label_block' => true,
				'description' => esc_html( \GV\Utils::get( $setting, 'desc', '' ) ),
			];

			// Match control types exactly with View Settings and Advanced Widget.
			switch ( $setting['type'] ) {
				case 'number':
					$control_type                    = Controls_Manager::NUMBER;
					$control_config['label_block']   = false;
					$control_config['min']           = \GV\Utils::get( $setting, 'min', '' );
					$control_config['max']           = \GV\Utils::get( $setting, 'max', '' );
					break;
				case 'select':
					$control_type                = Controls_Manager::SELECT;
					$control_config['options']   = isset( $setting['options'] ) ? $setting['options'] : [];
					break;
				default:
					$control_type = Controls_Manager::TEXT;
			}

			$control_config['type'] = $control_type;

			$this->add_control( $key, $control_config );
		}

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	protected function render() {

		if ( ! class_exists( '\GV\View' ) ) {
			return;
		}

		$settings = $this->get_settings_for_display();
		$view_id  = (int) \GV\Utils::get( $settings, 'embedded_view', 0 );

		if ( 0 === $view_id ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="text-align:center; padding:20px; border:1px dashed #ccc;">';
				echo esc_html__( 'Please select a View from the widget settings.', 'gk-gravityview' );
				echo '</div>';
			}
			return;
		}

		$view = \GV\View::by_id( $view_id );

		if ( ! $view ) {
			echo '<div style="text-align:center; padding:20px; border:1px dashed #ccc;">';
			echo esc_html__( 'View not found.', 'gk-gravityview' );
			echo '</div>';
			return;
		}

		// Build shortcode attributes.
		$atts = [ 'id' => $view_id ];

		// Add optional settings if they differ from defaults.
		$defaults = \GV\View_Settings::defaults( true );
		foreach ( [ 'page_size', 'sort_field', 'sort_direction' ] as $key ) {
			$value         = \GV\Utils::get( $settings, $key );
			$default_value = \GV\Utils::get( $defaults, $key . '/value' );

			if ( ! empty( $value ) && $value !== $default_value ) {
				$atts[ $key ] = $value;
			}
		}

		// Generate shortcode.
		$shortcode_atts = [];
		foreach ( $atts as $key => $value ) {
			$shortcode_atts[] = sprintf( '%s="%s"', $key, esc_attr( $value ) );
		}

		$secret = $view->get_validation_secret();

		if ( $secret ) {
			$shortcode_atts[] = sprintf( 'secret="%s"', $secret );
		}

		$shortcode = sprintf( '[gravityview %s]', implode( ' ', $shortcode_atts ) );

		// Render using existing GravityView renderer.
		$rendered = Blocks::render_shortcode( $shortcode );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $rendered['content'];
	}

	/**
	 * Get list of published Views for select control.
	 *
	 * @since TODO
	 *
	 * @return array List of Views with ID as key and title as value.
	 */
	private function get_views_list() {
		$views_data = GVCommon::get_views_list();

		$views_list = [ '0' => esc_html__( '— Select a View —', 'gk-gravityview' ) ];

		foreach ( $views_data as $id => $view ) {
			// translators: %1$s is the View title, %2$d is the View ID.
			$views_list[ $id ] = esc_html( sprintf( __( '%1$s (View #%2$d)', 'gk-gravityview' ), $view['title'], $id ) );
		}

		return $views_list;
	}

	/**
	 * Get Views layouts data for Advanced Widget compatibility.
	 *
	 * @since TODO
	 *
	 * @return array Array of View layouts indexed by View ID.
	 */
	private function get_views_layouts_data() {
		$views_data = GVCommon::get_views_list();

		$layouts = [];

		foreach ( $views_data as $id => $view ) {
			$layouts[ $id ] = [
				'single'   => $view['template_single_entry'],
				'multiple' => $view['template'],
			];
		}

		return $layouts;
	}
}
