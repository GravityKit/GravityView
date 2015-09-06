<?php


/**
 * Widget to display pagination info
 *
 * @extends GravityView_Widget
 */
class GravityView_Widget_Pagination_Info extends GravityView_Widget {

	/**
	 * Does this get displayed on a single entry?
	 * @var boolean
	 */
	protected $show_on_single = false;

	function __construct() {

		$this->widget_description = __('Summary of the number of visible entries out of the total results.', 'gravityview' );

		$default_values = array(
			'header' => 1,
			'footer' => 1,
		);

		$settings = array();

		parent::__construct( __( 'Show Pagination Info', 'gravityview' ) , 'page_info', $default_values, $settings );
	}

	public function render_frontend( $widget_args, $content = '', $context = '') {
		$gravityview_view = GravityView_View::getInstance();

		if( !$this->pre_render_frontend() ) {
			return;
		}

		if( !empty( $widget_args['title'] ) ) {
			echo $widget_args['title'];
		}

		$pagination_counts = $gravityview_view->getPaginationCounts();

		$total = $first = $last = null;

		$output = '';

		if( ! empty( $pagination_counts ) ) {

			$first = $pagination_counts['first'];
			$last = $pagination_counts['last'];
			$total = $pagination_counts['total'];

			$class = !empty( $widget_args['custom_class'] ) ? $widget_args['custom_class'] : '';
			$class = gravityview_sanitize_html_class( $class );

			$output = '<div class="gv-widget-pagination '.$class.'"><p>'. sprintf(__( 'Displaying %1$s - %2$s of %3$s', 'gravityview' ), number_format_i18n( $first ), number_format_i18n( $last ), number_format_i18n( $total ) ) . '</p></div>';
		}

		/**
		 * @filter `gravityview_pagination_output` Modify the pagination widget output
		 * @param string $output HTML output
		 * @param int $first First entry #
		 * @param int $last Last entry #
		 * @param int $total Total entries #
		 */
		echo apply_filters( 'gravityview_pagination_output', $output, $first, $last, $total );

	}

}

new GravityView_Widget_Pagination_Info;