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
	 *
	 * @var string
	 */
	protected $widget_label = '';

	/**
	 * Widget description, shown on the "+ Add Widget" picker
	 *
	 * @var  string
	 */
	protected $widget_description = '';

	/**
	 * Widget details, shown in the widget modal
	 *
	 * @since 1.8
	 * @var  string
	 */
	protected $widget_subtitle = '';

	/**
	 * Widget admin ID
	 *
	 * @var string
	 */
	protected $widget_id = '';

	/**
	 * Default configuration for header and footer
	 *
	 * @var array
	 */
	protected $defaults = array();

	/**
	 * Widget admin advanced settings
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Allow class to automatically add widget_text filter for you in shortcode
	 *
	 * @var string
	 */
	protected $shortcode_name;

	/**
	 * Hold the widget options.
	 *
	 * @var array()
	 */
	private $widget_options = array();

	/**
	 * The position of the widget.
	 *
	 * @api
	 * @since 2.0
	 * @var string
	 */
	public $position = '';

	/**
	 * A unique ID for this widget.
	 *
	 * @api
	 * @since 2.0
	 * @var string
	 */
	public $UID = '';

	/**
	 * The actual configuration for this widget instance.
	 *
	 * @api
	 * @since 2.0
	 * @var \GV\Settings
	 */
	public $configuration;

	/**
	 * @var string An icon that represents the widget type in the widget picker.
	 *
	 * Supports these icon formats:
	 * - Gravity Forms icon class: The string starts with "gform-icon". Note: the site must be running GF 2.5+. No need to also pass "gform-icon".
	 * - Dashicons: The string starts with "dashicons". No need to also pass "dashicons".
	 * - Inline SVG: Starts with "data:". Note: No single quotes are allowed!
	 * - If not matching those formats, the value will be used as a CSS class in a `<i>` element.
	 *
	 * @see GravityView_Admin_View_Item::getOutput
	 */
	public $icon;

	/**
	 * Constructor.
	 *
	 * @param string $label The Widget label as shown in the admin.
	 * @param string $id The Widget ID, make this something unique.
	 * @param array  $defaults Default footer/header Widget configuration.
	 * @param array  $settings Advanced Widget settings.
	 *
	 * @return \GV\Widget
	 */
	public function __construct( $label, $id, $defaults = array(), $settings = array() ) {
		/**
		 * The shortcode name is set to the lowercase name of the widget class, unless overridden by the class specifying a different value for $shortcode_name
		 *
		 * @var string
		 */
		$this->shortcode_name = empty( $this->shortcode_name ) ? strtolower( get_called_class() ) : $this->shortcode_name;

		if ( $id ) {
			$this->widget_id = $id;
		}

		$this->widget_label = $label;
		$this->defaults     = array_merge(
			array(
				'header' => 0,
				'footer' => 0,
			),
			$defaults
		);

		// Make sure every widget has a title, even if empty
		$this->settings = wp_parse_args( $settings, $this->get_default_settings() );

		// Hook once per unique ID
		if ( $this->is_registered() ) {
			return;
		}

		// widget options
		add_filter( 'gravityview_template_widget_options', array( $this, 'assign_widget_options' ), 10, 3 );

		// frontend logic
		add_action( sprintf( 'gravityview/widgets/%s/render', $this->get_widget_id() ), array( $this, 'render_frontend' ), 10, 3 );

		// register shortcodes
		add_action( 'wp', array( $this, 'add_shortcode' ) );

		// Use shortcodes in text widgets.
		add_filter( 'widget_text', array( $this, 'maybe_do_shortcode' ) );

		// register widgets to be listed in the View Configuration
		// Important: this has to be the last filter/action added in the constructor.
		add_filter( 'gravityview/widgets/register', array( $this, 'register_widget' ) );
	}

	/**
	 * Define general widget settings
	 *
	 * @since 1.5.4
	 * @return array $settings Default settings
	 */
	protected function get_default_settings() {
		$settings = array();

		/**
		 * Enable custom CSS class settings for widgets.
		 *
		 * @since 1.5.4
		 *
		 * @param bool       $enable_custom_class False by default. Return true if you want to enable.
		 * @param \GV\Widget $this                Current instance of \GV\Widget.
		 */
		$enable_custom_class = apply_filters( 'gravityview/widget/enable_custom_class', false, $this );

		if ( $enable_custom_class ) {
			$settings['custom_class'] = array(
				'type'       => 'text',
				'label'      => __( 'Custom CSS Class:', 'gk-gravityview' ),
				'desc'       => __( 'This class will be added to the widget container', 'gk-gravityview' ),
				'value'      => '',
				'merge_tags' => true,
				'class'      => 'widefat code',
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

		$default_areas = [
			Grid::get_row_by_type( '100' ),
			Grid::get_row_by_type( '50/50' ),
		];

		/**
		 * Array of zones available for widgets to be dropped into.
		 *
		 * @deprecated 2.0: Use gravityview/widget/active_areas instead
		 * @param array $default_areas Definition for default widget areas
		 */
		$default_areas = apply_filters( 'gravityview_widget_active_areas', $default_areas );

		/**
		 * Array of zones available for widgets to be dropped into.
		 *
		 * @since 2.0
		 * @param array $default_areas Definition for default widget areas
		 */
		return apply_filters( 'gravityview/widget/active_areas', $default_areas );
	}

	/**
	 * Register widget to become available in admin. And for lookup.
	 *
	 * @param  array $widgets Usually just empty. Used to gather them all up.
	 *
	 * @return array $widgets
	 */
	public function register_widget( $widgets ) {
		if ( ! is_array( $widgets ) ) {
			$widgets = array();
		}

		$widgets[ $this->get_widget_id() ] = [
			'label'       => $this->widget_label,
			'description' => $this->widget_description,
			'subtitle'    => $this->widget_subtitle,
			'icon'        => $this->icon,
			'class'       => static::class,
        ];

		return $widgets;
	}

	/**
	 * Assign template specific widget options
	 *
	 * @access protected
	 *
	 * @param array  $options (default: array())
	 * @param string $template (default: '')
	 *
	 * @return array
	 */
	public function assign_widget_options( $options = array(), $template = '', $widget = '' ) {
		if ( $this->get_widget_id() === $widget ) {
			if ( $settings = $this->get_settings() ) {
				$options = array_merge( $options, $settings );
			}
		}
		return $options;
	}

	/**
	 * Do shortcode if the Widget's shortcode exists.
	 *
	 * @param  string                                                                    $text   Widget text to check
	 * @param  null|\WP_Widget Empty if not called by WP_Widget, or a WP_Widget instance
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

		if ( ! gravityview()->plugin->is_compatible() ) {
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
			add_shortcode( $this->shortcode_name, '__return_null' );
			return;
		}

		add_shortcode( $this->shortcode_name, array( $this, 'render_shortcode' ) );
	}

	/**
	 * Frontend logic.
	 *
	 * Override in child class.
	 *
	 * @param array                       $widget_args The Widget shortcode args.
	 * @param string                      $content The content.
	 * @param string|\GV\Template_Context $context The context, if available.
	 *
	 * @return void
	 */
	public function render_frontend( $widget_args, $content = '', $context = '' ) {
	}

	/**
	 * Gets View considering the current context.
	 *
	 * @since 2.17.3
	 *
	 * @param string|\GV\Template_Context $context Context. Default: empty string.
	 *
	 * @return \GV\View|null
	 */
	public function get_view( $context = '' ) {

		// $context should be passed to pre_render_frontend() and render_frontend().
		if ( $context instanceof \GV\Template_Context && $context->view instanceof \GV\View ) {
			return $context->view;
		}

		// If it's not passed, parse the $post content.
		$views = gravityview()->views->get();

		// No views are found.
		if ( ! $views ) {
			return null;
		}

		// If there's only one view, return it.
		if ( $views instanceof \GV\View ) {
			return $views;
		}

		// If there are multiple views, return the first one.
		if ( $views instanceof \GV\View_Collection ) {
			gravityview()->log->debug( 'The widget lacks $context and there are multiple Views on this page. Returning the first.' );

			return $views->first();
		}

		return null;
	}

	/**
	 * General validations when rendering the widget
	 *
	 * Always call this from your `render_frontend()` override!
	 *
	 * @since 2.17.3 Added $context param.
	 *
	 * @param string|\GV\Template_Context $context Context. Default: empty string.
	 *
	 * @return boolean True: render frontend; False: don't render frontend
	 */
	public function pre_render_frontend( $context = '' ) {
		/**
		 * Assume shown regardless of hide_until_search setting.
		 */
		$allowlist = array(
			'custom_content',
		);

		/**
		 * @deprecated 2.14 In favor of allowlist.
		 */
		$allowlist = apply_filters_deprecated( 'gravityview/widget/hide_until_searched/whitelist', array( $allowlist ), '2.14', 'gravityview/widget/hide_until_searched/allowlist' );

		/**
		 * Some widgets have got to stay shown.
		 *
		 * @since 2.14
		 * @param string[] $allowlist The widget IDs that have to be shown by default.
		 */
		$allowlist = apply_filters( 'gravityview/widget/hide_until_searched/allowlist', $allowlist );

		$view = $this->get_view( $context );

		if ( $view && ! in_array( $this->get_widget_id(), $allowlist ) ) {
			$hide_until_searched = $view->settings->get( 'hide_until_searched' );
		} else {
			$hide_until_searched = false;
		}

		/**
		 * Modify whether to hide content until search.
		 *
		 * @since 1.5.4
		 *
		 * @param bool       $hide_until_searched Hide until search?
		 * @param \GV\Widget $this                Widget instance.
		 */
		$hide_until_searched = apply_filters( 'gravityview/widget/hide_until_searched', $hide_until_searched, $this );

		if ( $hide_until_searched && ! gravityview()->request->is_search() ) {
			gravityview()->log->debug( 'Hide View data until search is performed' );
			return false;
		}

		return true;
	}

	/**
	 * Shortcode.
	 *
	 * @param array                       $atts The Widget shortcode args.
	 * @param string                      $content The content.
	 * @param string|\GV\Template_Context $context The context, if available.
	 *
	 * @return string Whatever the widget echoed.
	 */
	public function render_shortcode( $atts, $content = '', $context = '' ) {
		ob_start();
		$this->render_frontend( $atts, $content, $context );
		return ob_get_clean();
	}

	/**
	 * Create the needed widget from a configuration array.
	 *
	 * @param array $configuration The configuration array.
	 * @see \GV\Widget::as_configuration()
	 * @internal
	 * @since 2.0
	 *
	 * @return \GV\Widget|null The widget implementation from configuration or none.
	 */
	public static function from_configuration( $configuration ) {
		$registered_widgets = self::registered();

		if ( ! $id = Utils::get( $configuration, 'id' ) ) {
			return null;
		}

		if ( ! $widget = Utils::get( $registered_widgets, $id ) ) {
			return null;
		}

		if ( ! class_exists( $class = Utils::get( $widget, 'class' ) ) ) {
			return null;
		}

		$w                = new $class( Utils::get( $widget, 'label' ), $id );
		$w->configuration = new Settings( $configuration );

		return $w;
	}

	/**
	 * Return an array of the old format.
	 *
	 *          'id' => string
	 *          + whatever else specific fields may have
	 *
	 * @internal
	 * @since 2.0
	 *
	 * @return array
	 */
	public function as_configuration() {
		return array_merge(
			array(
				'id' => $this->get_widget_id(),
			),
			$this->configuration->all()
		);
	}

	/**
	 * Return all registered widgets.
	 *
	 * @api
	 * @since 2.0
	 *
	 * @return array
	 */
	public static function registered() {
		/**
		 * Get the list of registered widgets. Each item is used to instantiate a GravityView_Admin_View_Widget object.
		 *
		 * @deprecated Use `gravityview/widgets/register`
		 * @param array $registered_widgets Empty array
		 */
		$registered_widgets = apply_filters( 'gravityview_register_directory_widgets', array() );

		/**
		 * Each item is used to instantiate a GravityView_Admin_View_Widget object.
		 *
		 * @since 2.0
		 *
		 * @param array $registered_widgets Empty array.
		 */
		return apply_filters( 'gravityview/widgets/register', $registered_widgets );
	}

	/**
	 * Whether this Widget's been registered already or not.
	 *
	 * @api
	 * @since 2.0
	 *
	 * @return bool
	 */
	public function is_registered() {
		if ( ! $widget_id = $this->get_widget_id() ) {
			gravityview()->log->warning( 'Widget ID not set before calling Widget::is_registered', array( 'data' => $this ) );
			return false;
		}
		return in_array( $widget_id, array_keys( self::registered() ), true );
	}
}
