<?php
/**
 * GravityView default widgets and generic widget class
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */



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
		global $gravityview_view;

		if( empty( $gravityview_view )) {
			do_action('gravityview_log_debug', sprintf( '%s[render_frontend]: $gravityview_view not instantiated yet.', get_class($this)) );
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

		$output = '<div class="gv-widget-pagination"><p>'. sprintf(__( 'Displaying %1$s - %2$s of %3$s', 'gravityview' ), $first , $last , $total ) . '</p></div>';

		echo apply_filters( 'gravityview_pagination_output', $output, $first, $last, $total );

	}

} // GravityView_Widget_Pagination_Info



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
		global $gravityview_view, $post;

		if( empty( $gravityview_view )) {
			do_action('gravityview_log_debug', sprintf( '%s[render_frontend]: $gravityview_view not instantiated yet.', get_class($this)) );

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
			'base' => add_query_arg('pagenum','%#%'),
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
			echo '<div class="gv-widget-page-links">'. $page_links .'</div>';
		} else {
			do_action( 'gravityview_log_debug', 'GravityView_Widget_Page_Links[render_frontend] No page links; paginate_links() returned empty response.' );
		}

	}

} // GravityView_Widget_Page_Links


/**
 * Main GravityView widget class
 */
class GravityView_Widget {

	/**
	 * Widget admin label
	 * @var string
	 */
	protected $widget_label;

	/**
	 * Widget description
	 * @var  string
	 */
	protected $widget_description;

	/**
	 * Widget admin id
	 * @var string
	 */
	protected $widget_id;

	/**
	 * default configuration for header and footer
	 * @var array
	 */
	protected $defaults;

	/**
	 * Widget admin advanced settings
	 * @var array
	 */
	protected $settings = array();

	/**
	 * allow class to automatically add widget_text filter for you in shortcode
	 * @var string
	 */
	protected $shortcode_name;

	// hold widget View options
	private $widget_options;

	function __construct( $widget_label , $widget_id , $defaults = array(), $settings = array() ) {


		/**
		 * The shortcode name is set to the lowercase name of the widget class, unless overridden by the class specifying a different value for $shortcode_name
		 * @var string
		 */
		$this->shortcode_name = !isset( $this->shortcode_name ) ? strtolower( get_class($this) ) : $this->shortcode_name;

		$this->widget_label = $widget_label;
		$this->widget_id = $widget_id;
		$this->defaults = array_merge( array( 'header' => 0, 'footer' => 0 ), $defaults );

		// Make sure every widget has a title, even if empty
		$this->settings = wp_parse_args( $settings, $this->settings );

		// register widgets to be listed in the View Configuration
		add_filter( 'gravityview_register_directory_widgets', array( $this, 'register_widget') );

		// widget options
		add_filter( 'gravityview_template_widget_options', array( $this, 'assign_widget_options' ), 10, 3 );

		// frontend logic
		add_action( "gravityview_render_widget_{$widget_id}", array( $this, 'render_frontend' ), 10, 1 );

		// register shortcodes
		add_action( 'wp', array( $this, 'add_shortcode') );

		// Use shortcodes in text widgets.
		add_filter('widget_text', array( $this, 'maybe_do_shortcode' ) );
	}

	/**
	 * Get the widget settings
	 * @return array|null   Settings array; NULL if not set
	 */
	public function get_settings() {
		return !empty( $this->settings ) ? $this->settings : NULL;
	}

	/**
	 * Get a setting by the setting key
	 * @param  string $key Key for the setting
	 * @return mixed|null      Value of the setting; NULL if not set
	 */
	public function get_setting( $key ) {
		if( isset( $this->settings ) && is_array( $this->settings ) ) {

			return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : NULL;
		}
	}

	/**
	 * Do shortcode if the Widget's shortcode exists.
	 * @param  string $text   Widget text to check
	 * @param  null|WP_Widget Empty if not called by WP_Widget, or a WP_Widget instance
	 * @return string         Widget text
	 */
	function maybe_do_shortcode( $text, $widget = NULL ) {

		if( !empty( $this->shortcode_name ) && has_shortcode( $text, $this->shortcode_name ) ) {
			return do_shortcode( $text );
		}

		return $text;
	}

	function render_shortcode( $atts, $content = '', $context = '' ) {

		ob_start();

		$this->render_frontend( $atts, $content, $context );

		return ob_get_clean();
	}

	/**
	 * Add $this->shortcode_name shortcode to output self::render_frontend()
	 */
	function add_shortcode( $run_on_singular = true ) {
		global $gravityview_view, $post;

		if( is_admin() ) { return; }

		if( empty( $this->shortcode_name ) ) { return; }

		// If the widget shouldn't output on single entries, don't show it
		if( empty( $this->show_on_single ) && class_exists('GravityView_frontend') && GravityView_frontend::is_single_entry() ) {
			do_action('gravityview_log_debug', sprintf( '%s[add_shortcode]: Skipping; set to not run on single entry.', get_class($this)) );

			add_shortcode( $this->shortcode_name, '__return_null' );
			return;
		}


		if( !has_gravityview_shortcode( $post ) ) {

			do_action('gravityview_log_debug', sprintf( '%s[add_shortcode]: No shortcode present; not adding render_frontend shortcode.', get_class($this)) );

			add_shortcode( $this->shortcode_name, '__return_null' );
			return;
		}

		add_shortcode( $this->shortcode_name, array( $this, 'render_shortcode') );
	}

	/**
	 * Register widget to become available in admin
	 * @param  array $widgets
	 * @return array $widgets
	 */
	function register_widget( $widgets ) {
		$widgets[ $this->widget_id ] = array(
			'label' => $this->widget_label ,
			'description' => $this->widget_description,
		);
		return $widgets;
	}

	/**
	 * Assign template specific field options
	 *
	 * @access protected
	 * @param array $options (default: array())
	 * @param string $template (default: '')
	 * @return void
	 */
	public function assign_widget_options( $options = array(), $template = '', $widget = '' ) {

		if( $this->widget_id === $widget ) {
			$options = array_merge( $options, $this->settings );
		}

		return $options;
	}


	/** Frontend logic */
	public function render_frontend( $widget_args, $content = '', $context = '') {
		// to be defined by child class
	}


} // GravityView_Widget

new GravityView_Widget_Pagination_Info;
new GravityView_Widget_Page_Links;
