<?php
/**
 * Integrate with the FancyBox lightbox and gallery scripts
 * @see https://fancyapps.com/fancybox/3/docs/#options
 * @since TODO
 */

/**
 * Register the FancyBox lightbox
 *
 * @internal
 */
class GravityView_Lightbox_Provider_FancyBox extends GravityView_Lightbox_Provider {

	public static $slug = 'fancybox';

	public static $script_slug = 'gravityview-fancybox';

	public static $style_slug = 'gravityview-fancybox';

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
						'CLOSE'       => __( 'Close', 'gravityview' ),
						'NEXT'        => __( 'Next', 'gravityview' ),
						'PREV'        => __( 'Previous', 'gravityview' ),
						'ERROR'       => __( 'The requested content cannot be loaded. Please try again later.', 'gravityview' ),
						'PLAY_START'  => __( 'Start slideshow', 'gravityview' ),
						'PLAY_STOP'   => __( 'Pause slideshow', 'gravityview' ),
						'FULL_SCREEN' => __( 'Full screen', 'gravityview' ),
						'THUMBS'      => __( 'Thumbnails', 'gravityview' ),
						'DOWNLOAD'    => __( 'Download', 'gravityview' ),
						'SHARE'       => __( 'Share', 'gravityview' ),
						'ZOOM'        => __( 'Zoom', 'gravityview' ),
					),
				)
		);

		return $defaults;
	}

	/**
	 * @inheritDoc
	 */
	public function output_footer() {

		$settings = self::get_settings();

		$settings = json_encode( $settings );

		?>
		<style>
			.fancybox-container {
				z-index: 100000; /** Divi is 99999 */
			}

			.admin-bar .fancybox-container {
				margin-top: 32px;
			}
		</style>
		<script>
			jQuery( '.gravityview-fancybox' ).fancybox(<?php echo $settings; ?>);
		</script>
		<?php
	}

	/**
	 * @inheritDoc
	 */
	public function enqueue_scripts() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( self::$script_slug, plugins_url( 'assets/lib/fancybox/dist/jquery.fancybox' . $min . '.js', GRAVITYVIEW_FILE ), array( 'jquery' ), GV_PLUGIN_VERSION );
	}

	/**
	 * @inheritDoc
	 */
	public function enqueue_styles() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_style( self::$style_slug, plugins_url( 'assets/lib/fancybox/dist/jquery.fancybox' . $min . '.css', GRAVITYVIEW_FILE ), array(), GV_PLUGIN_VERSION );
	}

	/**
	 * @inheritDoc
	 */
	public function allowed_atts( $atts = array() ) {

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

		return $atts;
	}

	/**
	 * @inheritDoc
	 */
	public function fileupload_link_atts( $link_atts, $field_compat = array(), $context = null ) {

		if ( ! $context->view->settings->get( 'lightbox', false ) ) {
			return $link_atts;
		}

		$link_atts['class'] = \GV\Utils::get( $link_atts, 'class' ) . ' gravityview-fancybox';

		$link_atts['class'] = sanitize_html_class( $link_atts['class'] );

		if ( $context && ! empty( $context->field->field ) ) {
			if ( $context->field->field->multipleFiles ) {
				$entry = $context->entry->as_entry();
				$link_atts['data-fancybox'] = 'gallery-' . sprintf( "%s-%s-%s", $entry['form_id'], $context->field->ID, $context->entry->get_slug() );
			}
		}

		return $link_atts;
	}

}

GravityView_Lightbox::register( 'GravityView_Lightbox_Provider_FancyBox' );