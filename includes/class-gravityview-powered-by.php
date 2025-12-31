<?php


/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * Add a Powered By link below Views.
 *
 * @since 2.5.3
 */
class GravityView_Powered_By {

	const url = 'https://www.gravitykit.com/powered-by/';

	/**
	 * GravityView_Powered_By constructor.
	 */
	public function __construct() {
		add_action( 'gravityview/template/after', array( $this, 'maybe_add_link' ) );
	}

	/**
	 * Prints a HTML link to GravityView's site if "Powered By" GravityView setting is enabled
	 *
	 * @param \GV\Template_Context $context The context.
	 *
	 * @return void
	 */
	public function maybe_add_link( \GV\Template_Context $context ) {

		$powered_by = gravityview()->plugin->settings->get_gravitykit_setting( 'powered_by', 0 );

		if ( empty( $powered_by ) ) {
			return;
		}

		$url = $this->get_url();

		// Allow disabling link via URL filter
		if ( empty( $url ) ) {
			return;
		}

		$css_class = 'gv-powered-by';

		if ( ! $context->request->is_search() && isset( $context->entries ) && 0 === $context->entries->count() && 3 === (int) $context->view->settings->get( 'no_entries_options', '0' ) ) {
			$css_class .= ' gv-hidden';
		}

		/**
		 * Modify the anchor text for the Powered By link.
		 *
		 * @since 2.10
		 *
		 * @param string $anchor_text Anchor text for the Powered By link. Default: "Powered by GravityView". Will be sanitized before display.
		 */
		$anchor_text = apply_filters( 'gravityview/powered_by/text', __( 'Powered by GravityView', 'gk-gravityview' ) );

		printf( '<span class="%s"><a href="%s">%s</a></span>', esc_attr( $css_class ), esc_url( $url ), esc_html( $anchor_text ) );
	}

	/**
	 * Returns the URL to GravityView
	 *
	 * @return string URL to GravityView (not sanitized)
	 */
	protected function get_url() {

		$url = sprintf( self::url, get_bloginfo( 'name' ) );

		$affiliate_id = gravityview()->plugin->settings->get_gravitykit_setting( 'affiliate_id', '' );

		if ( $affiliate_id && is_numeric( $affiliate_id ) ) {
			$url = add_query_arg( array( 'ref' => $affiliate_id ), $url );
		}

		$url = add_query_arg(
			array(
				'utm_source' => 'powered_by',
				'utm_term'   => get_bloginfo( 'name' ),
			),
			$url
		);

		/**
		 * Modify the URL returned by the Powered By link.
		 *
		 * @since 2.10
		 *
		 * @param string $url The URL passed to the Powered By link.
		 */
		$url = apply_filters( 'gravityview/powered_by/url', $url );

		return $url;
	}
}

new GravityView_Powered_By();
