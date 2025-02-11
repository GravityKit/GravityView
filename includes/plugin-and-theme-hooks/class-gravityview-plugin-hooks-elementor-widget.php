<?php

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use GravityKit\GravityView\Gutenberg\Blocks;

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
	 * GravityView_Elementor_Widget constructor.
	 */
	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		add_action( 'elementor/init', [ $this, 'init' ] );
	}

	/**
	 * Initialize the GravityView widget
	 */
	public function init( $data ) {
		parent::init( $data );

		// Check if Elementor is installed and activated
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_missing_elementor' ] );
			return;
		}

		// Check if GravityView is installed and activated
		if ( ! class_exists( 'GravityView_Plugin' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_missing_gravityview' ] );
			return;
		}

		$this->init_layouts();
	}

	/**
	 * Admin notice for missing GravityView
	 */
	public function admin_notice_missing_gravityview() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
		/* translators: 1: Plugin name 2: GravityView */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'gravityview-elementor' ),
			'<strong>' . esc_html__( 'GravityView Elementor Integration', 'gravityview-elementor' ) . '</strong>',
			'<strong>' . esc_html__( 'GravityView', 'gravityview-elementor' ) . '</strong>'
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}

	/**
	 * Admin notice for missing Elementor
	 */
	public function admin_notice_missing_elementor() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
		/* translators: 1: Plugin name 2: Elementor */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'gravityview-elementor' ),
			'<strong>' . esc_html__( 'GravityView Elementor Integration', 'gravityview-elementor' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'gravityview-elementor' ) . '</strong>'
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}

	public function get_script_depends() {
		if ( ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return [];
		}

		return [
			'gravityview-elementor-widget',
		];
	}

	/**
	 * Initialize available layouts and their settings
	 */
	private function init_layouts() {

		// TODO: Load these dynamically from the plugin, reading from available layouts.
		$this->view_layouts = [
			'default_table'          => [
				'label'           => __( 'Table Layout', 'gravityview-elementor' ),
				'template_id'     => 'default_table',
				'class'           => 'gv-table-view',
				'settings'        => [
					'has_header'          => true,
					'has_footer'          => true,
					'supports_datatables' => true,
				],
				'style_selectors' => [
					'wrapper' => '.gv-table-view',
					'header'  => '.gv-table-view thead th',
					'rows'    => '.gv-table-view tbody tr',
					'cells'   => '.gv-table-view tbody td',
				],
			],
			'default_list'          => [
				'label'           => __( 'List Layout', 'gravityview-elementor' ),
				'template_id'     => 'default_list',
				'class'           => 'gv-list-container',
				'settings'        => [
					'has_header'          => false,
					'has_footer'          => false,
					'supports_datatables' => false,
				],
				'style_selectors' => [
					'wrapper' => '.gv-list-container',
					'header'  => '.gv-table-view thead th',
					'rows'    => '.gv-table-view tbody tr',
					'cells'   => '.gv-table-view tbody td',
				],
			],
			'datatables_table'     => [
				'label'           => __( 'DataTables Layout', 'gravityview-elementor' ),
				'template_id'     => 'datatables',
				'class'           => 'gv-datatables-view',
				'settings'        => [
					'has_header'          => true,
					'has_footer'          => true,
					'supports_datatables' => true,
					'has_search'          => true,
					'has_pagination'      => true,
				],
				'style_selectors' => [
					'wrapper'    => '.gv-datatables-view',
					'header'     => '.gv-datatables-view thead th',
					'rows'       => '.gv-datatables-view tbody tr',
					'cells'      => '.gv-datatables-view tbody td',
					'pagination' => '.dataTables_pagination',
					'search'     => '.dataTables_filter',
				],
			],
			'diy'            => [
				'label'           => __( 'DIY Layout', 'gravityview-elementor' ),
				'template_id'     => 'custom',
				'class'           => 'gv-diy-view',
				'settings'        => [
					'has_container'       => true,
					'supports_custom_css' => true,
				],
				'style_selectors' => [
					'wrapper'     => '.gv-diy-view',
					'container'   => '.gv-diy-container',
					'entry'       => '.gv-diy-entry',
					'field_label' => '.gv-field-label',
					'field_value' => '.gv-field-value',
				],
			],
			'layout_builder' => [
				'label'           => __( 'Layout Builder', 'gravityview-elementor' ),
				'template_id'     => 'layout_builder',
				'class'           => 'gv-layout-builder-view',
				'settings'        => [
					'has_grid'            => true,
					'supports_custom_css' => true,
				],
				'style_selectors' => [
					'wrapper' => '.gv-layout-builder-view',
					'grid'    => '.gv-grid',
					'columns' => '.gv-grid-col',
					'items'   => '.gv-grid-col-item',
				],
			],
		];
	}

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
		return '<svg fill="none" height="28" viewBox="0 -5 80 80" width="28" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path clip-rule="evenodd" d="m70.6842 63.9999h-9.2135v3c0 4.9706-4.1537 9-9.2134 9h-24.5683c-5.1266 0-9.2135-4.0294-9.2135-9v-44.9999h-9.21341c-1.73812 0-3.07072 1.343-3.07072 2.9999v30c0 1.657 1.3326 3.0001 3.07072 3.0001h1.53601c.8068 0 1.5347.6715 1.5347 1.5v3c0 .8284-.7279 1.4999-1.5347 1.4999h-1.53601c-5.13114 0-9.213445-4.0294-9.213445-9v-30c0-4.9705 4.082305-9 9.213445-9h9.21341v-2.9999c0-4.97062 4.0869-9 9.2135-9h24.5683c5.0597 0 9.2134 4.02938 9.2134 9v45h9.2135c1.6711 0 3.0707-1.3431 3.0707-3.0001v-30c0-1.6569-1.3996-2.9999-3.0707-2.9999h-1.536c-.8736 0-1.536-.6716-1.536-1.5v-3.0001c0-.8284.6624-1.5 1.536-1.5h1.536c5.0642 0 9.2121 4.0295 9.2121 9v30c0 4.9706-4.1479 9-9.2121 9zm-15.3562-50.9999c0-1.657-1.404-3-3.0707-3h-24.5683c-1.7335 0-3.0708 1.343-3.0708 3v53.9999c0 1.6568 1.3373 3.0001 3.0708 3.0001h24.5683c1.6667 0 3.0707-1.3433 3.0707-3.0001z" fill="#40464d" fill-rule="evenodd"/></svg>';
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
			'section_view',
			[
				'label' => esc_html__( 'Select a View', 'gk-gravityview' ),
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

		$views = get_posts([
			'post_type'      => 'gravityview',
			'posts_per_page' => -1,
		]);

		$views_options = [];
		$views_layouts = [];

		foreach ($views as $view) {
			$views_options[$view->ID] = $view->post_title;

			try {
				$gv_view = \GV\View::by_id($view->ID);
				if ($gv_view) {
					$views_layouts[$view->ID] = [
						'multiple' => $gv_view->settings->get('template'),
						'single' => $gv_view->settings->get('template_single_entry') ?? $gv_view->settings->get('template'),
					];
				}
			} catch (Exception $e) {
				// Skip if view can't be loaded
				continue;
			}
		}

		// Store layouts data for use in JS
		$this->add_control(
			'views_layouts',
			[
				'type' => \Elementor\Controls_Manager::HIDDEN,
				'default' => json_encode($views_layouts),
			]
		);

		// Hidden control for multiple layout
		$this->add_control(
			'layout_single',
			[
				'type' => \Elementor\Controls_Manager::HIDDEN,
				'default' => '',
			]
		);

		// Hidden control for multiple layout
		$this->add_control(
			'layout_multiple',
			[
				'type' => \Elementor\Controls_Manager::HIDDEN,
				'default' => '',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_view_settings',
			[
				'label' => esc_html__( 'Customize View Settings', 'gk-gravityview' ),
			]
		);

		$defaults = \GV\View_Settings::defaults( true );

		foreach( $defaults as $key => $default_setting ) {

			if ( empty( $default_setting['show_in_shortcode'] ) ) {
				continue;
			}

			$control_settings = [
				'label'       => $default_setting['label'],
				'default'     => $view_settings[ $key ] ?? $default_setting['value'],
				'label_block' => true,
			];

			switch ( $default_setting['type'] ) {
				case 'text':
				case 'textarea':
					$type = Controls_Manager::TEXT;
					break;
				case 'number':
					$type = Controls_Manager::NUMBER;
					break;
				case 'checkbox':
					$type = Controls_Manager::SWITCHER;
					break;
				case 'select':
				case 'radio':
					$type = Controls_Manager::SELECT;
					$control_settings['options'] = $default_setting['options'];
					break;
				default:
					$type = Controls_Manager::TEXT;
			}

			$control_settings['type'] = $type;

			$this->add_control(
				$key,
				$control_settings
			);
		}

		$this->end_controls_section();

		$this->register_style_controls();
	}

	protected function register_style_controls() {
		// Preview Section
		$this->start_controls_section(
			'gravityview_preview_section',
			[
				'label' => esc_html__( 'Preview', 'gk-gravityview' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'preview_single_entry',
			[
				'label'        => __( 'Preview Single Entry', 'gk-gravityview' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'gk-gravityview' ),
				'label_off'    => __( 'No', 'gk-gravityview' ),
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);

		$this->add_control(
			'show_debug_output',
			[
				'label'        => __( 'Show Debug Output', 'gk-gravityview' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'gk-gravityview' ),
				'label_off'    => __( 'No', 'gk-gravityview' ),
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);

		$this->end_controls_section();

		// Define base selectors for multiple entries and single entry views
		$multiple_selector = '{{WRAPPER}} [id^=gv-view-] .gv-table-multiple-container';
		$single_selector = '{{WRAPPER}} .gv-table-single-container .gv-table-view-content';

		$sections = [
			'table'         => [
				'label'    => esc_html__( 'Table', 'gk-gravityview' ),
				'controls' => [
					[
						'name'       => 'width',
						'type'       => \Elementor\Controls_Manager::SLIDER,
						'label'      => esc_html__( 'Width', 'gk-gravityview' ),
						'devices' => [ 'desktop', 'tablet', 'mobile' ],
						'desktop_default' => [
							'unit' => '%',
							'size' => '100',
						],
						'tablet_default' => [
							'unit' => '%',
							'size' => '100',
						],
						'mobile_default' => [
							'unit' => '%',
							'size' => '100',
						],
						'size_units' => [ '%', 'px' ],
						'range'      => [
							'%'  => [ 'min' => 10, 'max' => 100 ],
							'px' => [ 'min' => 100, 'max' => 2500 ],
						],
						'selectors'  => [
							'multiple' => [
								"$multiple_selector" => 'width: {{SIZE}}{{UNIT}};overflow-x: auto;',
							],
							'single'   => [
								"$single_selector" => 'width: {{SIZE}}{{UNIT}};overflow-x: auto;',
							],
						],
					],
				],
#				'condition' => [
#					'layout_single' => [ 'default_table', 'datatables_table' ],
#				],
			],
			'header_footer' => [
				'label'    => esc_html__( 'Header & Footer', 'gk-gravityview' ),
				'controls' => [
					[
						'name'      => 'text_color',
						'type'      => \Elementor\Controls_Manager::COLOR,
						'label'     => esc_html__( 'Text Color', 'gk-gravityview' ),
						'selectors' => [
							'multiple' => [
								"$multiple_selector thead th" => 'color: {{VALUE}};',
								"$multiple_selector tfoot th" => 'color: {{VALUE}};',
							],
							'single'   => [
								"$single_selector tr th" => 'color: {{VALUE}};',
							],
						],
					],
					[
						'name'      => 'background_color',
						'type'      => \Elementor\Controls_Manager::COLOR,
						'label'     => esc_html__( 'Background Color', 'gk-gravityview' ),
						'selectors' => [
							'multiple' => [
								"$multiple_selector thead" => 'background-color: {{VALUE}};',
								"$multiple_selector tfoot" => 'background-color: {{VALUE}};',
							],
							'single'   => [
								"$single_selector tr th" => 'background-color: {{VALUE}};',
							],
						],
					],
					[
						'name'      => 'text_align',
						'type'      => \Elementor\Controls_Manager::CHOOSE,
						'label'     => esc_html__( 'Text Alignment', 'gk-gravityview' ),
						'options'   => [
							'left'   => [
								'title' => __( 'Left', 'gk-gravityview' ),
								'icon'  => 'eicon-text-align-left',
							],
							'center' => [
								'title' => __( 'Center', 'gk-gravityview' ),
								'icon'  => 'eicon-text-align-center',
							],
							'right'  => [
								'title' => __( 'Right', 'gk-gravityview' ),
								'icon'  => 'eicon-text-align-right',
							],
						],
						'selectors' => [
							'multiple' => [
								"$multiple_selector thead th" => 'text-align: {{VALUE}};',
								"$multiple_selector tfoot th" => 'text-align: {{VALUE}};',
							],
							'single'   => [
								"$single_selector tr th" => 'text-align: {{VALUE}};',
							],
						],
					],
					[
						'name'      => 'hover_background_color',
						'type'      => \Elementor\Controls_Manager::COLOR,
						'label'     => esc_html__( 'Hover Background Color', 'gk-gravityview' ),
						'selectors' => [
							'multiple' => [
								"$multiple_selector thead:hover th" => 'background-color: {{VALUE}};',
								"$multiple_selector tfoot:hover th" => 'background-color: {{VALUE}};',
							],
							'single'   => [
								"$single_selector tr:hover th" => 'background-color: {{VALUE}};',
							],
						],
					],
					[
						'name'      => 'hover_text_color',
						'type'      => \Elementor\Controls_Manager::COLOR,
						'label'     => esc_html__( 'Hover Text Color', 'gk-gravityview' ),
						'selectors' => [
							'multiple' => [
								"$multiple_selector thead:hover th" => 'color: {{VALUE}};',
								"$multiple_selector tfoot:hover th" => 'color: {{VALUE}};',
							],
							'single'   => [
								"$single_selector tr:hover th" => 'color: {{VALUE}};',
							],
						],
					],
				],
			],
			'body'          => [
				'label'    => esc_html__( 'Body', 'gk-gravityview' ),
				'controls' => [
					[
						'name'      => 'text_color',
						'type'      => \Elementor\Controls_Manager::COLOR,
						'label'     => esc_html__( 'Text Color', 'gk-gravityview' ),
						'selectors' => [
							'multiple' => [
								"$multiple_selector tbody td" => 'color: {{VALUE}};',
							],
							'single'   => [
								"$single_selector tbody td" => 'color: {{VALUE}};',
							],
						],
					],
					[
						'name'      => 'background_color',
						'type'      => \Elementor\Controls_Manager::COLOR,
						'label'     => esc_html__( 'Background Color', 'gk-gravityview' ),
						'selectors' => [
							'multiple' => [
								"$multiple_selector tbody tr" => 'background-color: {{VALUE}};',
							],
							'single'   => [
								"$single_selector tbody tr" => 'background-color: {{VALUE}};',
							],
						],
					],
					[
						'name'      => 'hover_background_color',
						'type'      => \Elementor\Controls_Manager::COLOR,
						'label'     => esc_html__( 'Hover Background Color', 'gk-gravityview' ),
						'selectors' => [
							'multiple' => [
								"$multiple_selector tbody tr:hover" => 'background-color: {{VALUE}};',
							],
							'single'   => [
								"$single_selector tbody tr:hover" => 'background-color: {{VALUE}};',
							],
						],
					],
					[
						'name'      => 'hover_text_color',
						'type'      => \Elementor\Controls_Manager::COLOR,
						'label'     => esc_html__( 'Hover Text Color', 'gk-gravityview' ),
						'selectors' => [
							'multiple' => [
								"$multiple_selector tbody tr:hover td" => 'color: {{VALUE}};',
							],
							'single'   => [
								"$single_selector tbody tr:hover td" => 'color: {{VALUE}};',
							],
						],
					],
					[
						'name'      => 'text_align',
						'type'      => \Elementor\Controls_Manager::CHOOSE,
						'label'     => esc_html__( 'Text Alignment', 'gk-gravityview' ),
						'options'   => [
							'left'   => [
								'title' => __( 'Left', 'gk-gravityview' ),
								'icon'  => 'eicon-text-align-left',
							],
							'center' => [
								'title' => __( 'Center', 'gk-gravityview' ),
								'icon'  => 'eicon-text-align-center',
							],
							'right'  => [
								'title' => __( 'Right', 'gk-gravityview' ),
								'icon'  => 'eicon-text-align-right',
							],
						],
						'selectors' => [
							'multiple' => [
								"$multiple_selector tbody td" => 'text-align: {{VALUE}};',
								"$multiple_selector tbody td" => 'text-align: {{VALUE}};',
							],
							'single'   => [
								"$single_selector tr td" => 'text-align: {{VALUE}};',
							],
						],
					],
				],
			],
		];

		// Register sections with tabs
		foreach ( $sections as $section_name => $section_data ) {
			$this->start_controls_section(
				"gravityview_{$section_name}_style_section",
				[
					'label' => $section_data['label'],
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				]
			);

			$this->start_controls_tabs( "{$section_name}_context_tabs" );

			$contexts = [
				'multiple_entries' => esc_html__( 'Multiple Entries', 'gk-gravityview' ),
				'single_entry'     => esc_html__( 'Single Entry', 'gk-gravityview' ),
			];
			foreach( $contexts as $context => $context_label ) {
				$this->start_controls_tab( "{$section_name}_{$context}_tab", [
					'label' => esc_html( $context_label ),
				] );

				foreach ( $section_data['controls'] as $control ) {
					$control_args = [
						'label'      => $control['label'],
						'type'       => $control['type'],
						'options'    => $control['options'] ?? null,
						'size_units' => $control['size_units'] ?? [],
						'range'      => $control['range'] ?? [],
						'selector'   => $control['selector']['single'] ?? '',
						'selectors'  => $control['selectors']['multiple'] ?? [],
					];

					// Only set the default value if it's set in the control.
					if ( isset( $control['default'] ) ) {
						$control_args['default'] = $control['default'];
					}

					// Responsive controls.
					if ( isset( $control['devices'] ) ) {
						$control_args['devices'] = $control['devices'];
						$control_args['desktop_default'] = $control['desktop_default'];
						$control_args['tablet_default'] = $control['tablet_default'];
						$control_args['mobile_default'] = $control['mobile_default'];

						$this->add_responsive_control(
							"gravityview_{$section_name}_{$control['name']}_{$context}",
							$control_args
						);

					} else {
						$this->add_control(
							"gravityview_{$section_name}_{$control['name']}_{$context}",
							$control_args
						);
					}

				}

				$this->end_controls_tab();
			}
			$this->end_controls_tabs();
			$this->end_controls_section();
		}
	}

	/**
	 * Render GravityView widget output on the frontend.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();

		$view_id = (int) $settings['embedded_view'];

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

		$view = \GV\View::by_id( $view_id );

		if ( ! $view ) {
			echo esc_html__( 'View not found.', 'gk-gravityview' );
			return;
		}

		add_filter( 'gk/gravityview/entry-approval/hide-notice', '__return_true' );

		$atts_string = '';
		$atts = $this->convert_widget_settings_to_shortcode( $settings );

		$atts['id'] = $view->ID;

		// Only add the secret if the current user can edit the View.
		// This is to prevent the secret from being exposed to users who shouldn't see it.
		if( current_user_can( 'edit_gravityview', $view->ID ) ) {
			$secret = $view->get_validation_secret();
			if ( $secret ) {
				$atts['secret'] = $secret;
			}
		}

		// Convert array to shortcode attributes string
		foreach ( $atts as $key => $value ) {
			$atts_string .= sprintf( ' %s="%s"', $key, esc_attr( $value ) );
		}

		$shortcode = sprintf( '[gravityview %s]', $atts_string );

		$preview_single_entry = 'yes' === \GV\Utils::get( $settings, 'preview_single_entry' );
		$show_debug_output = 'yes' === \GV\Utils::get( $settings, 'show_debug_output' );

		echo '<div style="font-size: .8em; background-color: #fff; padding: 10px; margin-bottom: 0; border-bottom: 1px dashed var(--e-a-border-color-bold);">';
			if ( $preview_single_entry ) {
				echo '<p style="margin:0; "><span style="line-height:1.15;" class="dashicons dashicons-media-default"></span> <strong>' . esc_html__( 'Single Entry Preview', 'gk-gravityview' ) . '</strong></p>';
			} else {
				echo '<p style="margin:0;"><span style="line-height:1.15;" class="dashicons dashicons-admin-page"></span> <strong>' . esc_html__( 'Multiple Entries Preview', 'gk-gravityview' ) . '</strong></p>';
			}

			if ( $show_debug_output ) {
				echo '<p style="margin-top:1em; padding-top:0;"><strong>' . esc_html__( 'Shortcode', 'gk-gravityview' ) . '</strong></p>';
				echo '<code>' . $shortcode . '</code>';
			}
		echo '</div>';

		$custom_css = $view->settings->get( 'custom_css', null );

		if ( $custom_css ) {
			wp_add_inline_style( 'gravityview_default_style', $custom_css );
		}


		if ( $preview_single_entry ) {
			// Get the first entry in a View.
			$entry     = $view->get_entries()->first();
			$shortcode = sprintf( '[gventry entry_id="%d" view_id="%d" secret="%s"]', $entry->ID, $view_id, $secret );
		}

		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() && wp_doing_ajax() && 'elementor_ajax' === \GV\Utils::_POST( 'action' ) ) {
			add_filter( 'gravityview_email_prevent_encrypt', '__return_true' );
		}

		// Needs to be above the frontend rendering to ensure the Views are loaded.
		$rendered = Blocks::render_shortcode( $shortcode );

		$gravityview_frontend = \GravityView_frontend::getInstance();
		$gravityview_frontend->setGvOutputData( \GravityView_View_Data::getInstance( $shortcode ) );
		$gravityview_frontend->add_scripts_and_styles();

		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() && wp_doing_ajax() && 'elementor_ajax' === \GV\Utils::_POST( 'action' ) ) {
			wp_print_styles();
		}

		echo $rendered['content'];
	}

	public function get_data( $item = null ) {

		$this->settings = $this->get_default_data();

		if( null === $item ) {
			$item = [];
		}

		return parent::get_data( $item );
	}

	private function convert_widget_settings_to_shortcode( $settings ) {

		$defaults = \GV\View_Settings::defaults( true );

		$atts = [];
		foreach( $defaults as $key => $view_setting ) {

			if ( empty( $view_setting['show_in_shortcode'] ) ) {
				continue;
			}

			$passed_value = ( is_null( $settings[ $key ] ) || '' === $settings[ $key ] ) ? $view_setting['value'] : $settings[ $key ] ?? '';

			switch ( $view_setting['type'] ) {
				case 'number':
					$atts[ $key ] = (int) $passed_value;
					break;
				case 'checkbox':
					// Convert checkbox values to 1 or 0
					if ( 'yes' === $settings[ $key ] ) {
						$atts[ $key ] = 1;
					} else {
						$atts[ $key ] = 0;
					}
					break;
				default:
					$atts[ $key ] = $passed_value;
					break;
			}
		}

		return $atts;
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
			$views[ $view->ID ] = esc_html( sprintf( '%s #%d', $view->post_title, $view->ID ) );
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
