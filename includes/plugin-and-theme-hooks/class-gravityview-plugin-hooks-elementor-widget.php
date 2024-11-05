<?php

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! class_exists( 'Elementor\Widget_Base' ) ) {
	return;
}

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Gravity Forms Widget for Elementor
 *
 * Integrates Gravity Forms with Elementor page builder.
 *
 * @since 1.0.0
 */
class GravityView_Elementor_Widget extends Widget_Base {

	const ELEMENT_KEY = 'gk-gravityview';

	/**
	 * Retrieve Gravity Forms widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'gk_elementor_gravityview';
	}

	/**
	 * Retrieve Gravity Forms widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'GravityView', 'gk-gravityview' );
	}

	/**
	 * Retrieve the list of categories the Gravity Forms widget belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'basic' ];
	}

	/**
	 * Retrieve Gravity Forms widget keywords.
	 *
	 * @since 1.0.0
	 * @access public
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
			'table',
			'datatable',
			'map',
		];
	}

	/**
	 * Get widget icon.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'gk-gravityview-icon';
	}

	/**
	 * Get widget custom icon.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget custom icon.
	 */
	public static function get_custom_icon() {
		return '<svg fill="none" height="80" viewBox="0 0 80 80" width="80" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path clip-rule="evenodd" d="m70.6842 63.9999h-9.2135v3c0 4.9706-4.1537 9-9.2134 9h-24.5683c-5.1266 0-9.2135-4.0294-9.2135-9v-44.9999h-9.21341c-1.73812 0-3.07072 1.343-3.07072 2.9999v30c0 1.657 1.3326 3.0001 3.07072 3.0001h1.53601c.8068 0 1.5347.6715 1.5347 1.5v3c0 .8284-.7279 1.4999-1.5347 1.4999h-1.53601c-5.13114 0-9.213445-4.0294-9.213445-9v-30c0-4.9705 4.082305-9 9.213445-9h9.21341v-2.9999c0-4.97062 4.0869-9 9.2135-9h24.5683c5.0597 0 9.2134 4.02938 9.2134 9v45h9.2135c1.6711 0 3.0707-1.3431 3.0707-3.0001v-30c0-1.6569-1.3996-2.9999-3.0707-2.9999h-1.536c-.8736 0-1.536-.6716-1.536-1.5v-3.0001c0-.8284.6624-1.5 1.536-1.5h1.536c5.0642 0 9.2121 4.0295 9.2121 9v30c0 4.9706-4.1479 9-9.2121 9zm-15.3562-50.9999c0-1.657-1.404-3-3.0707-3h-24.5683c-1.7335 0-3.0708 1.343-3.0708 3v53.9999c0 1.6568 1.3373 3.0001 3.0708 3.0001h24.5683c1.6667 0 3.0707-1.3433 3.0707-3.0001z" fill="#40464d" fill-rule="evenodd"/></svg>';
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	/**
	 * Register Gravity Forms widget controls.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_form',
			[
				'label' => esc_html__( 'View Settings', 'gk-gravityview' ),
			]
		);

		$this->add_control(
			'embedded_view',
			[
				'label'       => __( 'Select View', 'gk-gravityview' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->get_views_list(),
				'default'     => '0',
				'label_block' => true,
			]
		);

		$this->end_controls_section();

		$this->register_style_controls();
	}

	protected function register_style_controls() {

		// Table Style Controls
		$this->start_controls_section(
			'gravityview_table_style_section',
			[
				'label' => esc_html__( 'Table', 'gk-gravityview' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
				'conditions' => [
					'terms' => [
						[
							'name' => 'embedded_view',
							'operator' => 'contains',
							'value' => 'table',
						],
					],
				],
			]
		);

		$this->add_control(
			'gravityview_table_width',
			[
				'label' => esc_html__( 'Width', 'gk-gravityview' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ '%', 'px' ],
				'range' => [
					'%' => [ 'min' => 10, 'max' => 100 ],
					'px' => [ 'min' => 100, 'max' => 2000 ],
				],
				'selectors' => [
					'{{WRAPPER}} .gv-table-container .gv-table-view' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name' => 'gravityview_table_border',
				'label' => esc_html__( 'Border', 'gk-gravityview' ),
				'selector' => '{{WRAPPER}} .gv-table-container .gv-table-view',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'gravityview_table_background',
				'label' => esc_html__( 'Background', 'gk-gravityview' ),
				'types' => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .gv-table-container .gv-table-view',
			]
		);

		$this->add_control(
			'gravityview_table_cell_spacing',
			[
				'label' => esc_html__( 'Cell Spacing', 'gk-gravityview' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'range' => [
					'px' => [ 'min' => 0, 'max' => 50 ],
				],
				'selectors' => [
					'{{WRAPPER}} .gv-table-container .gv-table-view' => 'border-spacing: {{SIZE}}px;',
				],
			]
		);

		$this->add_control(
			'gravityview_table_cell_padding',
			[
				'label' => esc_html__( 'Cell Padding', 'gk-gravityview' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors' => [
					'{{WRAPPER}} .gv-table-container .gv-table-view td, {{WRAPPER}} .gv-table-container .gv-table-view th' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'gravityview_table_margin',
			[
				'label' => esc_html__( 'Margin', 'gk-gravityview' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors' => [
					'{{WRAPPER}} .gv-table-container .gv-table-view' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'gravityview_table_text_align',
			[
				'label' => esc_html__( 'Table Text Alignment', 'gk-gravityview' ),
				'type' => \Elementor\Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => __( 'Left', 'gk-gravityview' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'gk-gravityview' ),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => __( 'Right', 'gk-gravityview' ),
						'icon' => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .gv-table-container .gv-table-view' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'gravityview_table_hover_background_color',
			[
				'label' => esc_html__( 'Hover Background Color', 'gk-gravityview' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gv-table-container .gv-table-view tbody tr:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'gravityview_table_hover_text_color',
			[
				'label' => esc_html__( 'Hover Text Color', 'gk-gravityview' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gv-table-container .gv-table-view tbody tr:hover td' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

// Header Style Controls
		$this->start_controls_section(
			'gravityview_table_header_style_section',
			[
				'label' => esc_html__( 'Header & Footer', 'gk-gravityview' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'gravityview_table_header_text_color',
			[
				'label' => esc_html__( 'Text Color', 'gk-gravityview' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gv-table-container .gv-table-view thead th' => 'color: {{VALUE}};',
					'{{WRAPPER}} .gv-table-container .gv-table-view tfoot th' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'gravityview_table_header_typography',
				'label' => esc_html__( 'Typography', 'gk-gravityview' ),
				'selectors' => [
					'{{WRAPPER}} .gv-table-container .gv-table-view thead th',
					'{{WRAPPER}} .gv-table-container .gv-table-view tfoot th',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'gravityview_table_header_background',
				'label' => esc_html__( 'Background', 'gk-gravityview' ),
				'types' => [ 'classic' ],
				'selectors' => [
					'{{WRAPPER}} .gv-table-container .gv-table-view thead',
					'{{WRAPPER}} .gv-table-container .gv-table-view tfoot',
				],
			]
		);

		$this->add_control(
			'gravityview_table_header_text_align',
			[
				'label' => esc_html__( 'Text Alignment', 'gk-gravityview' ),
				'type' => \Elementor\Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => __( 'Left', 'gk-gravityview' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'gk-gravityview' ),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => __( 'Right', 'gk-gravityview' ),
						'icon' => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .gv-table-container .gv-table-view thead th' => 'text-align: {{VALUE}};',
					'{{WRAPPER}} .gv-table-container .gv-table-view tfoot th' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'gravityview_table_header_hover_background_color',
			[
				'label' => esc_html__( 'Hover Background Color', 'gk-gravityview' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gv-table-container .gv-table-view thead th:hover' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .gv-table-container .gv-table-view tfoot th:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'gravityview_table_header_hover_text_color',
			[
				'label' => esc_html__( 'Hover Text Color', 'gk-gravityview' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gv-table-container .gv-table-view thead th:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .gv-table-container .gv-table-view tfoot th:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		// Body Style Controls
		$this->start_controls_section(
			'gravityview_table_body_style_section',
			[
				'label' => esc_html__( 'Body', 'gk-gravityview' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'gravityview_table_body_text_color',
			[
				'label' => esc_html__( 'Text Color', 'gk-gravityview' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gv-table-container .gv-table-view tbody td' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'gravityview_table_body_typography',
				'label' => esc_html__( 'Typography', 'gk-gravityview' ),
				'selector' => '{{WRAPPER}} .gv-table-container .gv-table-view tbody td',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'gravityview_table_body_background',
				'label' => esc_html__( 'Background', 'gk-gravityview' ),
				'types' => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .gv-table-container .gv-table-view tbody',
			]
		);

		$this->add_control(
			'gravityview_table_body_text_align',
			[
				'label' => esc_html__( 'Body Text Alignment', 'gk-gravityview' ),
				'type' => \Elementor\Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => __( 'Left', 'gk-gravityview' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'gk-gravityview' ),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => __( 'Right', 'gk-gravityview' ),
						'icon' => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .gv-table-container .gv-table-view tbody td' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'gravityview_table_body_hover_background_color',
			[
				'label' => esc_html__( 'Body Hover Background Color', 'gk-gravityview' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gv-table-container .gv-table-view tbody tr:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'gravityview_table_body_hover_text_color',
			[
				'label' => esc_html__( 'Body Hover Text Color', 'gk-gravityview' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gv-table-container .gv-table-view tbody tr:hover td' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

	}

	/**
	 * Render Gravity Forms widget output on the frontend.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();

		$view_id = 0;
		$value = $settings['embedded_view'];

		if ( ! empty( $value ) ) {
			list( $view_id, $directory_template, $single_template ) = explode( ',', $value );
		}

		if ( 0 === $view_id ) {
			// Only show this message in the admin editor.
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo strtr( '
				<div style="text-align: center; width: 100%;">
					<div style="width:120px; margin: 0 auto;">{icon}</div>
					<p>{message}</p>
				</div>', [
					'{icon}'    => self::get_filled_icon(),
					'{message}' => esc_html__( 'Select a View from the widget settings.', 'gk-gravityview' ),
				] );
			}

			return;
		}

		$view_id = (int) $view_id;
		$view = \GV\View::by_id( $view_id );

		$secret = $view->get_validation_secret();

		$shortcode = sprintf( '[gravityview id="%s" secret="%s"]', $view_id, $secret );

		$custom_css = $view->settings->get( 'custom_css', null );

		if ( $custom_css ) {
			wp_add_inline_style( 'gravityview_default_style', $custom_css );
		}

		// Needs to be above the frontend rendering to ensure the Views are loaded.
		$rendered = \GravityKit\GravityView\Gutenberg\Blocks::render_shortcode( $shortcode );

		$gravityview_frontend = \GravityView_frontend::getInstance();
		$gravityview_frontend->setGvOutputData( \GravityView_View_Data::getInstance( $shortcode ) );
		$gravityview_frontend->add_scripts_and_styles();

		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			#wp_print_styles();
		}

		echo $rendered['content'];
	}

	/**
	 * Get Gravity Forms list.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @return array An associative array of Gravity Forms.
	 */
	private function get_views_list() {
		$views = [
			0 => __( 'Select a View', 'gk-gravityview' ),
		];

		$all_views = GVCommon::get_all_views();

		if ( empty( $all_views ) ) {
			return $views;
		}

		foreach ( $all_views as $view ) {
			$directory_template = gravityview_get_directory_entries_template_id( $view->ID );
			$single_template    = gravityview_get_single_entry_template_id( $view->ID );
			$option_value = sprintf( '%d,%s,%s', $view->ID, esc_attr( $directory_template ), esc_attr(  $single_template ) );
			$views[ $option_value ] = esc_html( sprintf('%s #%d', $view->post_title, $view->ID ) );
		}

		return $views;
	}

	/**
	 * Get icon SVG.
	 *
	 * @return string Icon SVG.
	 */
	protected static function get_icon_svg() {
		return '<svg viewBox="0 0 418.4 460.6"  xmlns="http://www.w3.org/2000/svg"><style>.st0{fill:#414141;stroke:#414141;stroke-width:11;stroke-miterlimit:10}</style><path class="st0" d="M209.2 15.8c11.6 0 22.4 2.6 30.5 7.3l133.7 77.2c16.8 9.6 30.5 33.3 30.5 52.8l.1 154.5c0 19.4-13.7 43.1-30.5 52.8l-133.8 77.2c-8.1 4.7-19 7.3-30.5 7.3-11.6 0-22.4-2.6-30.5-7.3L44.9 360.4c-16.8-9.7-30.5-33.4-30.5-52.8V153.1c0-19.5 13.7-43.2 30.5-52.8l133.8-77.2c8.1-4.7 19-7.3 30.5-7.3m0-1c-11.2 0-22.5 2.5-31 7.4L44.4 99.4c-17.1 9.8-31 34-31 53.7v154.5c0 19.7 13.9 43.8 31 53.7l133.8 77.2c8.5 4.9 19.7 7.4 31 7.4 11.2 0 22.5-2.5 31-7.4L374 361.3c17-9.8 31-34 31-53.7l-.1-154.5c0-19.7-13.9-43.9-31-53.7L240.2 22.2c-8.5-4.9-19.7-7.4-31-7.4z"/><path class="st0" d="M347.4 145.8v47.8H171.2c-11.3 0-19.6 3.3-26.2 10.4-14.9 15.8-22 45.9-23.1 61.2l-.1 1.1h176.5v-43.8h47.8v91.6H70.7c.1-5.1.8-28.6 5.3-55.8 4.6-28.1 14.3-66.1 34-87.2 15.9-16.8 36.6-25.4 61.6-25.4h175.8m1-.9H171.6c-25.3 0-46.3 8.7-62.3 25.7-38.6 41.1-39.6 144.6-39.6 144.6h277.4v-93.6h-49.8v43.8H122.9c1.1-16.3 8.6-45.5 22.8-60.6 6.4-6.9 14.5-10.1 25.5-10.1h177.2v-49.8z"/></svg>';
	}

	/**
	 * Get filled icon.
	 *
	 * @param string $color Icon color.
	 *
	 * @return string Filled icon.
	 */
	protected static function get_filled_icon() {
		return '<svg fill="none" height="80" viewBox="0 0 80 80" width="80" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path clip-rule="evenodd" d="m70.6842 63.9999h-9.2135v3c0 4.9706-4.1537 9-9.2134 9h-24.5683c-5.1266 0-9.2135-4.0294-9.2135-9v-44.9999h-9.21341c-1.73812 0-3.07072 1.343-3.07072 2.9999v30c0 1.657 1.3326 3.0001 3.07072 3.0001h1.53601c.8068 0 1.5347.6715 1.5347 1.5v3c0 .8284-.7279 1.4999-1.5347 1.4999h-1.53601c-5.13114 0-9.213445-4.0294-9.213445-9v-30c0-4.9705 4.082305-9 9.213445-9h9.21341v-2.9999c0-4.97062 4.0869-9 9.2135-9h24.5683c5.0597 0 9.2134 4.02938 9.2134 9v45h9.2135c1.6711 0 3.0707-1.3431 3.0707-3.0001v-30c0-1.6569-1.3996-2.9999-3.0707-2.9999h-1.536c-.8736 0-1.536-.6716-1.536-1.5v-3.0001c0-.8284.6624-1.5 1.536-1.5h1.536c5.0642 0 9.2121 4.0295 9.2121 9v30c0 4.9706-4.1479 9-9.2121 9zm-15.3562-50.9999c0-1.657-1.404-3-3.0707-3h-24.5683c-1.7335 0-3.0708 1.343-3.0708 3v53.9999c0 1.6568 1.3373 3.0001 3.0708 3.0001h24.5683c1.6667 0 3.0707-1.3433 3.0707-3.0001z" fill="#40464d" fill-rule="evenodd"/></svg>';
	}
}
