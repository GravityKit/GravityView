<?php
/**
 * Integrate with the FancyBox lightbox and gallery scripts
 *
 * @see https://fancyapps.com/fancybox/3/docs/#options
 * @since 2.10
 */

/**
 * Register the FancyBox lightbox
 *
 * @internal
 */
class GravityView_Lightbox_Provider_FancyBox extends GravityView_Lightbox_Provider {

	/**
	 * @inheritDoc
	 */
	public static $slug = 'fancybox';

	/**
	 * @inheritDoc
	 */
	public static $script_slug = 'gravityview-fancybox';

	/**
	 * @inheritDoc
	 */
	public static $style_slug = 'gravityview-fancybox';

	/**
	 * @inheritDoc
	 */
	public static $css_class_name = 'gravityview-fancybox';

	/**
	 * @inheritDoc
	 */
	public static $data_type_attribute = 'data-type';

	/**
	 * @inheritDoc
	 */
	public static $data_type_value = 'ajax';

	/**
	 * Add inline scripts and styles for FancyBox initialization.
	 *
	 * @since 2.45.1 Renamed from print_scripts() for clarity.
	 * @inheritDoc
	 */
	public function add_inline_scripts_and_styles() {
		// Ensure scripts are registered before adding inline content
		if ( ! wp_script_is( self::$script_slug, 'registered' ) ) {
			return;
		}

		if ( ! wp_style_is( self::$style_slug, 'registered' ) ) {
			return;
		}

		$settings = self::get_settings();

		// Ensure settings can be JSON encoded
		$settings_json = wp_json_encode( $settings );
		if ( false === $settings_json ) {
			return;
		}

		$css_class_name = esc_js( self::$css_class_name );

		// Add initialization script
		wp_add_inline_script(
			self::$script_slug,
			"if ( window.Fancybox ){ Fancybox.bind('.{$css_class_name}', {$settings_json}); }"
		);

		// Add custom styles
		wp_add_inline_style( self::$style_slug,
			".fancybox-container {
				z-index: 100000; /* Divi is 99999 */
			}
			.admin-bar .fancybox-container {
				margin-top: 32px;
			}"
		);
	}

	/**
	 * Legacy method for backward compatibility.
	 *
	 * @since 2.10
	 * @deprecated 2.45.1 Use add_inline_scripts_and_styles() instead.
	 */
	public function print_scripts() {
		_deprecated_function( __METHOD__, '2.45.1', __CLASS__ . '::add_inline_scripts_and_styles()' );
		$this->add_inline_scripts_and_styles();
	}

	/**
	 * Options to pass to Fancybox
	 *
	 * @see https://fancyapps.com/fancybox/3/docs/#options
	 *
	 * @return array
	 */
	protected function default_settings() {

		$defaults = array(
			'animationEffect' => 'fade',
			'toolbar'         => true,
			'closeExisting'   => true,
			'arrows'          => true,
			'buttons'         => array(
				'thumbs',
				'close',
			),
			'i18n'            => array(
				'en' => array(
					'CLOSE'       => __( 'Close', 'gk-gravityview' ),
					'NEXT'        => __( 'Next', 'gk-gravityview' ),
					'PREV'        => __( 'Previous', 'gk-gravityview' ),
					'ERROR'       => __( 'The requested content cannot be loaded. Please try again later.', 'gk-gravityview' ),
					'PLAY_START'  => __( 'Start slideshow', 'gk-gravityview' ),
					'PLAY_STOP'   => __( 'Pause slideshow', 'gk-gravityview' ),
					'FULL_SCREEN' => __( 'Full screen', 'gk-gravityview' ),
					'THUMBS'      => __( 'Thumbnails', 'gk-gravityview' ),
					'DOWNLOAD'    => __( 'Download', 'gk-gravityview' ),
					'SHARE'       => __( 'Share', 'gk-gravityview' ),
					'ZOOM'        => __( 'Zoom', 'gk-gravityview' ),
				),
			),
		);

		return $defaults;
	}

	/**
	 * @inheritDoc
	 */
	public function register_scripts() {
		wp_register_script( self::$script_slug, plugins_url( 'assets/lib/fancybox/dist/fancybox.umd.js', GRAVITYVIEW_FILE ), array(), GV_PLUGIN_VERSION );
	}

	/**
	 * @inheritDoc
	 */
	public function register_styles() {
		wp_register_style( self::$style_slug, plugins_url( 'assets/lib/fancybox/dist/fancybox.css', GRAVITYVIEW_FILE ), array(), GV_PLUGIN_VERSION );
	}

	/**
	 * @inheritDoc
	 */
	public function allowed_atts( $atts = array() ) {

		$atts = parent::allowed_atts( $atts );

		$atts['data-fancybox']         = null;
		$atts['data-fancybox-trigger'] = null;
		$atts['data-fancybox-index']   = null;
		$atts['data-src']              = null;
		$atts['data-type']             = null;
		$atts['data-width']            = null;
		$atts['data-height']           = null;
		$atts['data-srcset']           = null;
		$atts['data-caption']          = null;
		$atts['data-options']          = null;
		$atts['data-filter']           = null;
		$atts['data-preload']          = null;

		return $atts;
	}

	/**
	 * @inheritDoc
	 */
	public function fileupload_link_atts( $link_atts, $field_compat = array(), $context = null, $additional_details = null ) {
		// Early return if lightbox is not enabled for this context.
		if ( $context && ! $context->view->settings->get( 'lightbox', false ) ) {
			return $link_atts;
		}

		// Validate additional_details is an array.
		if ( ! is_array( $additional_details ) ) {
			return $link_atts;
		}

		// Prevent empty content from getting added to the lightbox gallery.
		if ( empty( $additional_details['file_path'] ) || ! empty( $additional_details['disable_lightbox'] ) ) {
			return $link_atts;
		}

		// Ensure link_atts is an array.
		if ( ! is_array( $link_atts ) ) {
			$link_atts = array();
		}

		// Add FancyBox CSS class.
		$existing_class = \GV\Utils::get( $link_atts, 'class', '' );
		$link_atts['class'] = gravityview_sanitize_html_class( trim( $existing_class . ' ' . self::$css_class_name ) );

		// Set up gallery grouping for multiple files.
		if ( $context && ! empty( $context->field->field ) && $context->field->field->multipleFiles ) {
			$entry = $context->entry->as_entry();
			$gallery_id = sprintf( '%s-%s-%s',
				esc_attr( $entry['form_id'] ),
				esc_attr( $context->field->ID ),
				esc_attr( $context->entry->get_slug() )
			);
			$link_atts['data-fancybox'] = 'gallery-' . $gallery_id;
		}

		$file_path = \GV\Utils::get( $additional_details, 'file_path', '' );

		/**
		 * For file types that require iframe (e.g., PDFs, text files), declare `pdf` media type.
		 * SVGs are not supported by Fancybox by default but render fine inside an iframe.
		 *
		 * @see https://web.archive.org/web/20230221135246/https://fancyapps.com/docs/ui/fancybox/#media-types
		 */
		if ( ! empty( $file_path ) ) {
			if ( false !== strpos( $file_path, 'gv-iframe' ) || preg_match( '/\.svg$/i', $file_path ) ) {
				$link_atts['data-type'] = 'pdf';
			}
		}

		return $link_atts;
	}
}

GravityView_Lightbox::register( 'GravityView_Lightbox_Provider_FancyBox' );
