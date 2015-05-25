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

	/**
	 * @todo add support for specifying poll field to display (via AJAX in field settings)
	 * @since 1.8
	 */
	function __construct() {

		$this->widget_description = __('Displays the results of Poll Fields that exist in the form.', 'gravityview' );

		$this->widget_subtitle = sprintf( _x('Note: this will display poll results for %sall form entries%s, not only the entries displayed in the View.', 'The string placeholders are for emphasis HTML', 'gravityview' ), '<em>', '</em>' );

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

		// frontend - add template path
		add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );

	}

	/**
	 * Include this extension templates path
	 * @since 1.8
	 * @param array $file_paths List of template paths ordered
	 */
	function add_template_path( $file_paths ) {

		$index = 126;

		// Index 100 is the default GravityView template path.
		$file_paths[ $index ] = plugin_dir_path( __FILE__ ) . 'templates/';

		return $file_paths;
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
	 * @see https://www.gravityhelp.com/documentation/article/polls-add-on/
	 *
	 * @since 1.8
	 */
	public function render_frontend( $widget_args, $content = '', $context = '') {

		if( !$this->pre_render_frontend() ) {
			return;
		}

		// Make sure the class is loaded in DataTables
		if( !class_exists( 'GFFormDisplay' ) ) {
			include_once( GFCommon::get_base_path() . '/form_display.php' );
		}

		$gravityview_view = GravityView_View::getInstance();

		$poll_fields = GFCommon::get_fields_by_type( $gravityview_view->getForm(), array( 'poll' ) );

		// If no poll fields, get outta here!
		if ( empty ( $poll_fields ) ) {
			return $text;
		}

		$this->enqueue_scripts_and_styles();

		$default_settings = array(
			'field' => 0,
			'style' => 'green',
			'percentages' => true,
			'counts' => true,
		);

		$widget_settings = $widget_args;

		$settings = wp_parse_args( $widget_settings, $default_settings );

		/**
		 * Modify the widget
		 */
		$settings = apply_filters( 'gravityview/widget/poll/settings', $settings );

		$percentages = empty( $settings['percentages'] ) ? 'false' : 'true';

		$counts = empty( $settings['counts'] ) ? 'false' : 'true';

		if( !empty( $settings['field'] ) ) {
			$merge_tag = sprintf( '{gpoll: field="%d" style="%s" percentages="%s" counts="%s"}', $settings['field'], $settings['style'], $percentages, $counts );
		} else {
			$merge_tag = sprintf( '{all_poll_results: style="%s" percentages="%s" counts="%s"}', $settings['style'], $percentages, $counts );
		}

		$gravityview_view->poll_merge_tag = $merge_tag;

		$gravityview_view->poll_settings = $settings;

		$gravityview_view->render('widget', 'poll', false );

	}

}

new GravityView_Widget_Poll;