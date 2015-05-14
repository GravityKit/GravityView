<?php

/**
 * Widget to add custom content
 *
 * @since 1.8
 *
 * @extends GravityView_Widget
 */
class GravityView_Widget_Poll extends GravityView_Widget {

	/**
	 * Does this get displayed on a single entry?
	 * @var boolean
	 */
	protected $show_on_single = false;

	function __construct() {

		$this->widget_description = __('Insert custom text or HTML as a widget', 'gravityview' );

		$default_values = array(
			'header' => 1,
			'footer' => 1,
		);

		$settings = array(
			'percentages' => array(
				'label' => __('Display Percentages'),
				'type' => 'checkbox',
				'value' => true,
				'tooltip' => __( 'Display results percentages as part of results? Supported values are: true, false. Defaults to "true".', 'gravityview' ),
			),
			'counts' => array(
				'label' => __('Display Counts'),
				'type' => 'checkbox',
				'value' => true,
				'tooltip' => __( 'Display number of times each choice has been selected when displaying results? Supported values are: true, false. Defaults to "true".', 'gravityview' ),
			),
			'style' => array(
				'type' => 'select',
				'label' => __('Style'),
				'tooltip' => __( 'The Polls Add-On currently supports 4 built in styles: red, green, orange, blue. Defaults to "green".', 'gravityview' ),
				'value' => 'green',
				'choices' => array(
					'green' => __('Green'),
					'blue' => __('Blue'),
					'red' => __('Red'),
					'orange' => __('Orange'),
				)
			)
		);

		parent::__construct( __( 'Poll Results', 'gravityview' ) , 'poll', $default_values, $settings );
	}

	/**
	 * Load the scripts and styles needed for the display of the poll widget
	 *
	 * @since 1.8
	 */
	private function enqueue_scripts_and_styles() {

		$GFPolls = GFPolls::get_instance();

		wp_enqueue_script('gpoll_js', $GFPolls->get_base_url() . '/js/gpoll.js', array('jquery'), $GFPolls->_version);

		$GFPolls->localize_scripts();

		wp_enqueue_style('gpoll_css', $GFPolls->get_base_url() . '/css/gpoll.css', null, $GFPolls->_version);
	}

	/**
	 * Render the widget
	 *
	 * @since 1.8
	 */
	public function render_frontend( $widget_args, $content = '', $context = '') {

		if( !$this->pre_render_frontend() ) {
			return;
		}

		if( !empty( $widget_args['title'] ) ) {
			echo $widget_args['title'];
		}

		// Make sure the class is loaded in DataTables
		if( !class_exists( 'GFFormDisplay' ) ) {
			include_once( GFCommon::get_base_path() . '/form_display.php' );
		}

		global $gravityview_view;

		$this->enqueue_scripts_and_styles();

		$default_settings = array(
			'action' => 'polls',
			'field' => 0,
			'id' => 0,
			'mode' => 'results',
			'display_results' => true,
			'ajax' => false,
			'disable_scripts' => false,
			'tabindex' => null,
			'title' => false,
			'description' => false,
			'style' => 'green',
			'percentages' => true,
			'counts' => true,
		);

		$widget_settings = array(
			'id' => $gravityview_view->getFormId(),
			'style' => $this->get_setting('style'),
			'percentages' => $this->get_setting('percentages'),
			'counts' => $this->get_setting('counts'),
		);

		$settings = wp_parse_args( $widget_settings, $default_settings );
		#var_dump( $settings );
		#die();

		foreach( $settings as $key => $value ) {

			$value = empty( $value ) ? 'false' : 'true';

			$shortcode_atts[] = $key.'="'.$value.'"';
		}

		$shortcode_string = implode( ' ', $shortcode_atts );

		$shortcode = "[gravityforms {$shortcode_string}]";

		echo do_shortcode($shortcode);

	}

}

new GravityView_Widget_Poll;