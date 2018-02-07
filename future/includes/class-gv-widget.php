<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The \GV\Widget class.
 *
 * An interface that most GravityView widgets would want to adhere to and inherit from.
 */
abstract class Widget {
	/**
	 * Widget admin label
	 * @var string
	 */
	protected $widget_label = '';

	/**
	 * Widget description, shown on the "+ Add Widget" picker
	 * @var  string
	 */
	protected $widget_description = '';

	/**
	 * Widget details, shown in the widget lightbox
	 * @since 1.8
	 * @var  string
	 */
	protected $widget_subtitle = '';

	/**
	 * Widget admin ID
	 * @var string
	 */
	protected $widget_id = '';

	/**
	 * Default configuration for header and footer
	 * @var array
	 */
	protected $defaults = array();

	/**
	 * Widget admin advanced settings
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Allow class to automatically add widget_text filter for you in shortcode
	 * @var string
	 */
	protected $shortcode_name;

	// hold widget View options
	private $widget_options;

	/**
	 * Constructor.
	 *
	 * @param string $label The Widget label as shown in the admin.
	 * @param string $id The Widget ID, make this something unique.
	 * @param array $defaults Default footer/header Widget configuration.
	 * @param array $settings Advanced Widget settings.
	 *
	 * @return \GV\Widget
	 */
	public function __construct( $label, $id, $defaults = array(), $settings = array() ) {
		/**
		 * The shortcode name is set to the lowercase name of the widget class, unless overridden by the class specifying a different value for $shortcode_name
		 * @var string
		 */
		$this->shortcode_name = empty( $this->shortcode_name ) ? strtolower( get_called_class() ) : $this->shortcode_name;

		$this->widget_label = $label;
		$this->widget_id = $id;
		$this->defaults = array_merge( array( 'header' => 0, 'footer' => 0 ), $defaults );

		// Make sure every widget has a title, even if empty
		$this->settings = wp_parse_args( $settings, $this->get_default_settings() );

		// register widgets to be listed in the View Configuration
		add_filter( 'gravityview_register_directory_widgets', array( $this, 'register_widget' ) );

		// widget options
		add_filter( 'gravityview_template_widget_options', array( $this, 'assign_widget_options' ), 10, 3 );

		// frontend logic
		add_action( "gravityview_render_widget_{$id}", array( $this, 'render_frontend' ), 10, 1 );

		// register shortcodes
		add_action( 'wp', array( $this, 'add_shortcode' ) );

		// Use shortcodes in text widgets.
		add_filter( 'widget_text', array( $this, 'maybe_do_shortcode' ) );
	}

	/**
	 * Define general widget settings
	 * @since 1.5.4
	 * @return array $settings Default settings
	 */
	protected function get_default_settings() {
		$settings = array();

		/**
		 * @filter `gravityview/widget/enable_custom_class` Enable custom CSS class settings for widgets
		 * @param boolean $enable_custom_class False by default. Return true if you want to enable.
		 * @param \GV\Widget $this Current instance of \GV\Widget.
		 */
		$enable_custom_class = apply_filters( 'gravityview/widget/enable_custom_class', false, $this );

		if ( $enable_custom_class ) {
			$settings['custom_class'] = array(
				'type' => 'text',
				'label' => __( 'Custom CSS Class:', 'gravityview' ),
				'desc' => __( 'This class will be added to the widget container', 'gravityview' ),
				'value' => '',
				'merge_tags' => true,
			);
		}

		return $settings;
	}

    /**
	 * Get the Widget ID.
	 *
     * @return string The Widget ID.
     */
    public function get_widget_id() {
        return $this->widget_id;
    }

	/**
	 * Get the widget settings
	 *
	 * @return array|null Settings array; NULL if not set for some reason.
	 */
	public function get_settings() {
		return empty( $this->settings ) ? null : $this->settings;
	}

	/**
	 * Get a setting by the setting key.
	 *
	 * @param  string $key Key for the setting
	 *
	 * @todo Use the \GV\Settings class later. For now subclasses may still expect and array instead.
	 *
	 * @return mixed|null Value of the setting; NULL if not set
	 */
	public function get_setting( $key ) {
		return Utils::get( $this->settings, $key, null );
	}

	/**
	 * Default widget areas.
	 *
	 * Usually overridden by the selected template.
	 *
	 * @return array The default areas where widgets can be rendered.
	 */
	public static function get_default_widget_areas() {
		$default_areas = array(
			array( '1-1' => array( array( 'areaid' => 'top', 'title' => __( 'Top', 'gravityview' ) , 'subtitle' => '' ) ) ),
			array( '1-2' => array( array( 'areaid' => 'left', 'title' => __( 'Left', 'gravityview' ) , 'subtitle' => '' ) ), '2-2' => array( array( 'areaid' => 'right', 'title' => __( 'Right', 'gravityview' ) , 'subtitle' => '' ) ) ),
		);

		/**
		 * @filter `gravityview_widget_active_areas` Array of zones available for widgets to be dropped into
		 * @deprecated Use gravityview/widget/active_areas
		 * @param array $default_areas Definition for default widget areas
		 */
		$default_areas = apply_filters( 'gravityview_widget_active_areas', $default_areas );

		/**
		 * @filter `gravityview/widget/active_areas` Array of zones available for widgets to be dropped into
		 * @param array $default_areas Definition for default widget areas
		 */
		return apply_filters( 'gravityview/widget/active_areas', $default_areas );
	}

	/**
	 * Register widget to become available in admin.
	 *
	 * @param  array $widgets
	 *
	 * @return array $widgets
	 */
	public function register_widget( $widgets ) {
		$widgets[ $this->get_widget_id() ] = array(
			'label' => $this->widget_label ,
			'description' => $this->widget_description,
			'subtitle' => $this->widget_subtitle,
		);
		return $widgets;
	}

	/**
	 * Assign template specific field options
	 *
	 * @access protected
	 *
	 * @param array $options (default: array())
	 * @param string $template (default: '')
	 *
	 * @return array
	 */
	public function assign_widget_options( $options = array(), $template = '', $widget = '' ) {
		if ( $this->get_widget_id() === $widget ) {
			$options = array_merge( $options, $this->get_settings() );
		}
		return $options;
	}

	/**
	 * Do shortcode if the Widget's shortcode exists.
	 *
	 * @param  string $text   Widget text to check
	 * @param  null|WP_Widget Empty if not called by WP_Widget, or a WP_Widget instance
	 *
	 * @return string         Widget text
	 */
	public function maybe_do_shortcode( $text, $widget = null ) {
		if ( ! empty( $this->shortcode_name ) && has_shortcode( $text, $this->shortcode_name ) ) {
			return do_shortcode( $text );
		}
		return $text;
	}

	/**
	 * Add $this->shortcode_name shortcode to output self::render_frontend()
	 *
	 * @return void
	 */
	public function add_shortcode() {
		if ( empty( $this->shortcode_name ) ) {
			return;
		}

		if ( gravityview()->request->is_admin() ) {
			return;
		}

		// If the widget shouldn't output on single entries, don't show it
		if ( empty( $this->show_on_single ) && gravityview()->request->is_entry() ) {
			gravityview()->log->debug( 'Skipping; set to not run on single entry.' );
			add_shortcode( $this->shortcode_name, '__return_null' );
			return;
		}

		global $post;

		if ( ! is_object( $post ) || empty( $post->post_content ) || ! Shortcode::parse( $post->post_content ) ) {
			gravityview()->log->debug( 'No shortcode present; not adding render_frontend shortcode.' );
			add_shortcode( $this->shortcode_name, '__return_null' );
			return;
		}

		add_shortcode( $this->shortcode_name, array( $this, 'render_shortcode') );
	}

	/**
	 * Frontend logic.
	 *
	 * Override in child class.
	 *
	 * @param array $widget_args The Widget shortcode args.
	 * @param string $content The content.
	 * @param string $context The context, if available.
	 *
	 * @return void
	 */
	public function render_frontend( $widget_args, $content = '', $context = '' ) {
	}

	/**
	 * General validations when rendering the widget
	 *
	 * @deprecated Not used. Hide until searched moved to the render_shortcode method.
	 *
	 * @return boolean True: render frontend; False: don't render frontend
	 */
	public function pre_render_frontend() {
	}

	/**
	 * Shortcode.
	 *
	 * @param array $atts The Widget shortcode args.
	 * @param string $content The content.
	 * @param string $context The context, if available.
	 *
	 * @return string Whatever the widget echoed.
	 */
	public function render_shortcode( $atts, $content = '', $context = '' ) {
		if ( $view = gravityview()->views->get() ) {
			$hide_until_searched = $view->settings->get( 'hide_until_searched' );
		} else {
			$hide_until_searched = false;
		}

		/**
		 * @filter `gravityview/widget/hide_until_searched` Modify whether to hide content until search
		 * @param boolean $hide_until_searched Hide until search?
		 * @param \GV\Widget $this Widget instance
		 */
		$hide_until_search = apply_filters( 'gravityview/widget/hide_until_searched', $hide_until_searched, $this );

		if ( $hide_until_search ) {
			gravityview()->log->debug( 'Hide View data until search is performed' );
			return;
		}

		ob_start();
		$this->render_frontend( $atts, $content, $context );
		return ob_get_clean();
	}
}
