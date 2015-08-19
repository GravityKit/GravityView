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

		$offset = $gravityview_view->paging['offset'];
		$page_size = $gravityview_view->paging['page_size'];
		$total = $gravityview_view->total_entries;

		if( empty( $total ) ) {
			do_action('gravityview_log_debug', sprintf( '%s[render_frontend]: No entries.', get_class($this)) );
			return;
		}

		// displaying info
		if( $total == 0 ) {
			$first = $last = 0;
		} else {
			$first = empty( $offset ) ? 1 : $offset + 1;
			$last = $offset + $page_size > $total ? $total : $offset + $page_size;
		}

		/**
		 * Modify the displayed pagination numbers
		 * @param array $counts Array with $first, $last, $total
		 * @var array array with $first, $last, $total numbers in that order.
		 */
		list( $first, $last, $total ) = apply_filters( 'gravityview_pagination_counts', array( $first, $last, $total ) );

		$class = !empty( $widget_args['custom_class'] ) ? $widget_args['custom_class'] : '';
		$class = gravityview_sanitize_html_class( $class );

		$output = '<div class="gv-widget-pagination '.$class.'"><p>'. sprintf(__( 'Displaying %1$s - %2$s of %3$s', 'gravityview' ), $first , $last , $total ) . '</p></div>';

		echo apply_filters( 'gravityview_pagination_output', $output, $first, $last, $total );

	}

}

new GravityView_Widget_Pagination_Info;