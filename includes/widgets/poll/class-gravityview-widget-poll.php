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
	 *
	 * @var boolean
	 */
	protected $show_on_single = false;

	/**
	 * @todo add support for specifying poll field to display (via AJAX in field settings)
	 * @since 1.8
	 */
	function __construct() {

		$this->widget_id          = 'poll';
		$this->icon               = 'dashicons-chart-bar';
		$this->widget_description = __( 'Displays the results of Poll Fields that exist in the form.', 'gk-gravityview' );
		$this->widget_subtitle    = sprintf( _x( 'Note: this will display poll results for %1$sall form entries%2$s, not only the entries displayed in the View.', 'The string placeholders are for emphasis HTML', 'gk-gravityview' ), '<em>', '</em>' );

		$default_values = array(
			'header' => 1,
			'footer' => 1,
		);

		$settings = array(
			'percentages' => array(
				'label'   => __( 'Display Percentages', 'gk-gravityview' ),
				'type'    => 'checkbox',
				'value'   => true,
				'tooltip' => __( 'Display results percentages as part of results? Supported values are: true, false. Defaults to "true".', 'gk-gravityview' ),
			),
			'counts'      => array(
				'label'   => __( 'Display Counts', 'gk-gravityview' ),
				'type'    => 'checkbox',
				'value'   => true,
				'tooltip' => __( 'Display number of times each choice has been selected when displaying results? Supported values are: true, false. Defaults to "true".', 'gk-gravityview' ),
			),
			'style'       => array(
				'type'    => 'select',
				'label'   => __( 'Style', 'gk-gravityview' ),
				'tooltip' => __( 'The Polls Add-On currently supports 4 built in styles: red, green, orange, blue. Defaults to "green".', 'gk-gravityview' ),
				'value'   => 'green',
				'choices' => array(
					'green'  => __( 'Green', 'gk-gravityview' ),
					'blue'   => __( 'Blue', 'gk-gravityview' ),
					'red'    => __( 'Red', 'gk-gravityview' ),
					'orange' => __( 'Orange', 'gk-gravityview' ),
				),
			),
		);

		if ( ! $this->is_registered() ) {
			// frontend - add template path
			add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );
		}

		parent::__construct( __( 'Poll Results', 'gk-gravityview' ), null, $default_values, $settings );
	}

	/**
	 * Include this extension templates path
	 *
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

		wp_enqueue_script( 'gpoll_js', $GFPolls->get_base_url() . '/js/gpoll.js', array( 'jquery' ), $GFPolls->_version );

		$GFPolls->localize_scripts();

		if ( version_compare( $GFPolls->_version, '4.0', '>=' ) ) {
			wp_enqueue_style( 'gpoll_css', $GFPolls->get_base_url() . '/assets/css/dist/theme.css', null, $GFPolls->_version );
		} else {
			wp_enqueue_style( 'gpoll_css', $GFPolls->get_base_url() . '/css/gpoll.css', null, $GFPolls->_version );
		}
	}

	/**
	 * @inheritDoc
	 *
	 * @since 1.8
	 * @since 2.17.3 Added $context param
	 *
	 * @param string|GV\Template_Context $context Context. Default: empty string.
	 */
	public function pre_render_frontend( $context = '' ) {

		if ( ! class_exists( 'GFPolls' ) ) {

			gravityview()->log->error( 'Poll Widget not displayed; the Poll Addon is not loaded' );

			return false;
		}

		$view = $this->get_view( $context );

		$poll_fields = array( $view->form->form['id'] => GFCommon::get_fields_by_type( $view->form, array( 'poll' ) ) );

		foreach ( $view->joins as $join ) {
			$poll_fields[ $join->join_on->form['id'] ] = GFCommon::get_fields_by_type( $join->join_on->form, array( 'poll' ) );
		}

		$poll_fields = array_filter( $poll_fields );

		if ( empty( $poll_fields ) ) {
			gravityview()->log->error( 'Poll Widget not displayed; there are no poll fields for the form' );
			return false;
		}

		$this->poll_fields = $poll_fields;

		return parent::pre_render_frontend( $context );
	}

	/**
	 * Get the display settings for the Poll widget
	 *
	 * @param array $widget_settings Settings for the Poll widget
	 *
	 * @return array Final poll widget settings
	 */
	function get_frontend_settings( $widget_settings ) {

		$default_settings = array(
			'field'       => 0,
			'style'       => 'green',
			'percentages' => true,
			'counts'      => true,
		);

		$settings = wp_parse_args( $widget_settings, $default_settings );

		/**
		 * Modifies display settings for the poll widget.
		 *
		 * @since 1.8
		 *
		 * @param array $settings Settings with `field`, `style`, `percentages` and `counts` keys.
		 */
		$settings = apply_filters( 'gravityview/widget/poll/settings', $settings );

		return $settings;
	}

	/**
	 * Render the widget
	 *
	 * @see https://www.gravityhelp.com/documentation/article/polls-add-on/
	 *
	 * @since 1.8
	 */
	public function render_frontend( $widget_args, $content = '', $context = '' ) {

		if ( ! $this->pre_render_frontend( $context ) ) {
			return;
		}

		// Make sure the class is loaded in DataTables
		if ( ! class_exists( 'GFFormDisplay' ) ) {
			include_once GFCommon::get_base_path() . '/form_display.php';
		}

		$this->enqueue_scripts_and_styles();

		$settings = $this->get_frontend_settings( $widget_args );

		$percentages = empty( $settings['percentages'] ) ? 'false' : 'true';

		$counts = empty( $settings['counts'] ) ? 'false' : 'true';

		if ( ! empty( $settings['field'] ) ) {
			$merge_tag = sprintf( '{gpoll: field="%d" style="%s" percentages="%s" counts="%s"}', $settings['field'], $settings['style'], $percentages, $counts );
		} else {
			$merge_tag = sprintf( '{all_poll_results: style="%s" percentages="%s" counts="%s"}', $settings['style'], $percentages, $counts );
		}

		$gravityview_view = GravityView_View::getInstance();

		$gravityview_view->poll_merge_tag = $merge_tag;

		$gravityview_view->poll_settings = $settings;
		$gravityview_view->poll_fields   = $this->poll_fields;

		$gravityview_view->render( 'widget', 'poll', false );

		unset( $gravityview_view->poll_merge_tag, $gravityview_view->poll_settings, $gravityview_view->poll_form, $gravityview_view->poll_fields );
	}
}

new GravityView_Widget_Poll();
