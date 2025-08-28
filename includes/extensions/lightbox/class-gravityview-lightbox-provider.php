<?php

/**
 * Registers a lightbox provider.
 *
 * @internal Currently internal; not ready for public usage.
 */
abstract class GravityView_Lightbox_Provider {

	/**
	 * The internal slug of the lightbox provider.
	 *
	 * @var string
	 */
	public static $slug;

	/**
	 * The slug of the registered script to use for the lightbox.
	 *
	 * @var string
	 */
	public static $script_slug;

	/**
	 * The slug of the registered style to use for the lightbox.
	 *
	 * @var string
	 */
	public static $style_slug;

	/**
	 * The CSS class name to use for the lightbox.
	 *
	 * @since 2.45
	 *
	 * @var string
	 */
	public static $css_class_name;

	/**
	 * The attribute to use to set the lightbox type.
	 *
	 * @since 2.45
	 *
	 * @var string
	 */
	public static $data_type_attribute = 'data-type';

	/**
	 * The type of data-type attribute to use for HTML attributes.
	 *
	 * @since 2.45
	 *
	 * @var string
	 */
	public static $data_type_value = 'ajax';

	/**
	 * Adds actions and that modify GravityView to use this lightbox provider
	 */
	public function add_hooks() {
		add_filter( 'gravityview_lightbox_script', array( $this, 'filter_lightbox_script' ), 1000 );
		add_filter( 'gravityview_lightbox_style', array( $this, 'filter_lightbox_style' ), 1000 );

		add_filter( 'gravityview/fields/fileupload/link_atts', array( $this, 'fileupload_link_atts' ), 10, 4 );
		add_filter( 'gravityview/get_link/allowed_atts', array( $this, 'allowed_atts' ) );
		add_filter( 'gravityview/shortcodes/gv_entry_link/output', array( $this, 'filter_entry_link_output' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );

		add_action( 'gravityview/template/after', array( $this, 'print_scripts_if_active' ) );

		add_action( 'wp_footer', array( $this, 'output_footer' ) );
	}

	/**
	 * Prints scripts for lightbox after a View is rendered if the provider is active.
	 *
	 * @since 2.45
	 *
	 * @param GV\Template_Context $context
	 *
	 * @return void
	 */
	public function print_scripts_if_active( $context ) {
		if ( ! $context instanceof \GV\Template_Context || ! self::is_active( $context ) ) {
			return;
		}

		$this->print_scripts();
	}

	/**
	 * Prints scripts for lightbox after a View is rendered
	 *
	 * @since 2.10.1
	 * @since 2.45 Changed to always print scripts, regardless of context.
	 *
	 * @return void
	 */
	protected function print_scripts() {
		static $did_print = false;

		if ( $did_print ) {
			return;
		}

		wp_print_scripts( static::$script_slug );
		wp_print_styles( static::$style_slug );

		$did_print = true;
	}

	/**
	 * Returns whether the provider is active for this View
	 *
	 * @since 2.10.1
	 *
	 * @param GV\Template_Context $context
	 *
	 * @return bool true: yes! false: no!
	 */
	protected static function is_active( $context ) {

		if ( ! $context instanceof \GV\Template_Context ) {
			return false;
		}

		$lightbox = $context->view->settings->get( 'lightbox' );

		if ( ! $lightbox ) {
			return false;
		}

		$provider = gravityview()->plugin->settings->get( 'lightbox', GravityView_Lightbox::DEFAULT_PROVIDER );

		if ( static::$slug !== $provider ) {
			return false;
		}

		return true;
	}

	/**
	 * Removes actions that were added by {@see GravityView_Lightbox_Provider::add_hooks}
	 *
	 * @internal Do not call directly. Instead, use:
	 *
	 * <code>
	 * do_action( 'gravityview/lightbox/provider', 'slug' );
	 * </code>
	 */
	public function remove_hooks() {
		remove_filter( 'gravityview_lightbox_script', array( $this, 'filter_lightbox_script' ), 1000 );
		remove_filter( 'gravityview_lightbox_style', array( $this, 'filter_lightbox_style' ), 1000 );

		remove_filter( 'gravityview/fields/fileupload/link_atts', array( $this, 'fileupload_link_atts' ), 10 );
		remove_filter( 'gravityview/get_link/allowed_atts', array( $this, 'allowed_atts' ) );

		remove_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		remove_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );

		remove_action( 'wp_footer', array( $this, 'output_footer' ) );
	}

	/**
	 * Modifies the name of the stylesheet to be enqueued when loading thickbox
	 *
	 * @param string $script
	 *
	 * @return string
	 */
	public function filter_lightbox_script( $script = 'thickbox' ) {
		return static::$script_slug;
	}

	/**
	 * Modifies the name of the stylesheet to be enqueued when loading thickbox
	 *
	 * @param string $style
	 *
	 * @return string
	 */
	public function filter_lightbox_style( $style = 'thickbox' ) {
		return static::$style_slug;
	}

	/**
	 * Get default settings for the script
	 *
	 * @return array
	 */
	protected function default_settings() {
		return array();
	}

	/**
	 * Get the settings for the JavaScript, with filter applied
	 *
	 * @internal
	 *
	 * @return mixed|void
	 */
	protected function get_settings() {
		$settings = static::default_settings();

		return apply_filters( 'gravityview/lightbox/provider/' . static::$slug . '/settings', $settings );
	}

	/**
	 * Output raw HTML in the wp_footer()
	 *
	 * @internal
	 */
	public function output_footer() {}

	/**
	 * Enqueue scripts for the lightbox
	 *
	 * @internal
	 */
	public function enqueue_scripts() {}

	/**
	 * Enqueue styles for the lightbox
	 *
	 * @internal
	 */
	public function enqueue_styles() {}

	/**
	 * Modify the attributes allowed in an anchor tag generated by GravityView
	 *
	 * @internal
	 *
	 * @param array $atts Attributes allowed in an anchor <a> tag.
	 *
	 * @return array
	 */
	public function allowed_atts( $atts = array() ) {
		$atts[ static::$data_type_attribute ] = null;
		return $atts;
	}

	/**
	 * Get the data-type attribute to use for the lightbox.
	 *
	 * @return string
	 */
	public function get_data_type_attribute() {
		return static::$data_type_attribute;
	}

	/**
	 * Get the data-type value to use for the lightbox.
	 *
	 * @return string
	 */
	public function get_data_type_value() {
		return static::$data_type_value;
	}

	/**
	 * Get the CSS class name to use for the lightbox.
	 *
	 * @return string
	 */
	public function get_css_class_name() {
		return static::$css_class_name;
	}

	/**
	 * Filter the output of the [gv_entry_link] shortcode
	 *
	 * @param string $output The HTML link output
	 * @param array {
	 *   @type string        $url The URL used to generate the anchor tag. {@see GravityView_Entry_Link_Shortcode::get_url}
	 *   @type string        $link_text {@see GravityView_Entry_Link_Shortcode::get_anchor_text}
	 *   @type array         $link_atts {@see GravityView_Entry_Link_Shortcode::get_link_atts}
	 *   @type array|string  $atts Shortcode atts passed to shortcode
	 *   @type string        $content Content passed to shortcode
	 *   @type string        $context The tag of the shortcode being called
	 * }
	 *
	 * @return string
	 */
	public function filter_entry_link_output( $output, $args ) {

		// Prevent errors when saving a post or page in the admin.
		if ( wp_doing_ajax() || GVCommon::is_rest_request() ) {
			return $output;
		}

		// If the lightbox attribute is not set, return the original HTML output.
		if ( empty( $args['atts']['lightbox'] ) ) {
			return $output;
		}

		// If the action is delete, return the original HTML output.
		if ( 'delete' === \GV\Utils::get( $args['atts'], 'action', '' ) ) {
			return $output;
		}

		$link_atts = $args['link_atts'];

		// Add the CSS class name to the link attributes.
		$css_class = \GV\Utils::get( $link_atts, 'class', '' ) . ' ' . $this->get_css_class_name();
		$link_atts['class'] = gravityview_sanitize_html_class( $css_class );
		$link_atts[ $this->get_data_type_attribute() ] = $this->get_data_type_value();

		// Generate the HTML link.
		$output = gravityview_get_link( $args['url'], $args['link_text'], $link_atts );

		// Print the scripts for the lightbox.
		$this->print_scripts();

		return $output;
	}

	/**
	 * Modified File Upload field links to use lightbox
	 *
	 * @since 2.10.1 Added $insecure_file_path
	 * @internal
	 *
	 * @param array|string              $link_atts Array or attributes string.
	 * @param array                     $field_compat Current GravityView field.
	 * @param \GV\Template_Context|null $context The context.
	 * @param array                     $additional_details Array of additional details about the file. {
	 * @type string $file_path URL to file.
	 * @type string $insecure_file_path URL to insecure file.
	 * }
	 *
	 * @return mixed
	 */
	public function fileupload_link_atts( $link_atts, $field_compat = array(), $context = null, $additional_details = null ) {
		return $link_atts;
	}
}
