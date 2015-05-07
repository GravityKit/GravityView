<?php

/**
 * Widget to display page links
 *
 * @extends GravityView_Widget
 */
class GravityView_Widget_Page_Links extends GravityView_Widget {

	protected $show_on_single = false;

	function __construct() {

		$this->widget_description = __('Links to multiple pages of results.', 'gravityview' );

		$default_values = array( 'header' => 1, 'footer' => 1 );
		$settings = array( 'show_all' => array(
			'type' => 'checkbox',
			'label' => __( 'Show each page number', 'gravityview' ),
			'desc' => __('Show every page number instead of summary (eg: 1 2 3 ... 8 »)', 'gravityview'),
			'value' => false
		));
		parent::__construct( __( 'Page Links', 'gravityview' ) , 'page_links', $default_values, $settings );

	}

	public function render_frontend( $widget_args, $content = '', $context = '') {
		$gravityview_view = GravityView_View::getInstance();

		if( !$this->pre_render_frontend() ) {
			return;
		}

		$page_size = $gravityview_view->paging['page_size'];
		$total = $gravityview_view->total_entries;

		$atts = shortcode_atts( array(
			'show_all' => !empty( $this->settings['show_all']['default'] ),
		), $widget_args, 'gravityview_widget_page_links' );

		// displaying info
		$curr_page = empty( $_GET['pagenum'] ) ? 1 : intval( $_GET['pagenum'] );

		$page_link_args = array(
			'base' => add_query_arg('pagenum','%#%', gv_directory_link() ),
			'format' => '&pagenum=%#%',
			'add_args' => array(), //
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'type' => 'list',
			'end_size' => 1,
			'mid_size' => 2,
			'total' => empty( $page_size ) ? 0 : ceil( $total / $page_size ),
			'current' => $curr_page,
			'show_all' => !empty( $atts['show_all'] ), // to be available at backoffice
		);

		/**
		 * Filter the pagination options
		 *
		 * @since 1.1.4
		 *
		 * @param array  $page_link_args Array of arguments for the `paginate_links()` function
		 * @link http://developer.wordpress.org/reference/functions/paginate_links/ Read more about `paginate_links()`
		 */
		$page_link_args = apply_filters('gravityview_page_links_args', $page_link_args );

		$page_links = paginate_links( $page_link_args );

		if( !empty( $page_links )) {
			$class = !empty( $widget_args['custom_class'] ) ? $widget_args['custom_class'] : '';
			$class = gravityview_sanitize_html_class( $class );
			echo '<div class="gv-widget-page-links '.$class.'">'. $page_links .'</div>';
		} else {
			do_action( 'gravityview_log_debug', 'GravityView_Widget_Page_Links[render_frontend] No page links; paginate_links() returned empty response.' );
		}

	}

}

new GravityView_Widget_Page_Links;