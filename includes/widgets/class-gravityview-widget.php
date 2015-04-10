<?php

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
		$this->settings = $this->get_default_settings();
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
	 * Define general widget settings
	 * @since 1.5.4
	 * @return array $settings Default settings
	 */
	protected function get_default_settings() {

		$settings = array();

		/**
		 * Enable custom class for widgets
		 * @param boolean False by default. Return true if you want to enable.
		 * @param GravityView_Widget Current instance of GravityView_Widget
		 */
		$enable_custom_class = apply_filters('gravityview/widget/enable_custom_class', false, $this );

		if( $enable_custom_class ) {

			$settings['custom_class'] = array(
				'type' => 'text',
				'label' => __( 'Custom CSS Class:', 'gravityview' ),
				'desc' => __( 'This class will be added to the widget container', 'gravityview'),
				'value' => '',
				'merge_tags' => true,
			);

		}

		return $settings;
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
		global $post;

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
		if( !$this->pre_render_frontend() ) {
			return;
		}
	}

	/**
	 * General validations when rendering the widget
	 */
	public function pre_render_frontend() {
		$gravityview_view = GravityView_View::getInstance();

		if( empty( $gravityview_view ) ) {
			do_action('gravityview_log_debug', sprintf( '%s[render_frontend]: $gravityview_view not instantiated yet.', get_class($this)) );
			return false;
		}

		if( apply_filters( 'gravityview/widget/hide_until_searched', $gravityview_view->hide_until_searched, $this ) ) {
			do_action('gravityview_log_debug', sprintf( '%s[render_frontend]: Hide View data until search is performed', get_class($this)) );
			return false;
		}

		return true;
	}


} // GravityView_Widget

