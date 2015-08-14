<?php
/**
 * [gravityview] Shortcode class
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.13
 */

/**
 * Handle the [gravityview] shortcode
 *
 * @since 1.13
 */
class GravityView_Shortcode {

	function __construct() {
		$this->add_hooks();
	}

	private function add_hooks() {

		// Shortcode to render view (directory)
		add_shortcode( 'gravityview', array( $this, 'shortcode' ) );
	}

	/**
	 * Callback function for add_shortcode()
	 *
	 * @since 1.13
	 *
	 * @access public
	 * @static
	 * @param mixed $atts
	 * @return null|string If admin, null. Otherwise, output of $this->render_view()
	 */
	function shortcode( $atts, $content = null ) {

		// Don't process when saving post.
		if ( is_admin() ) {
			return null;
		}

		do_action( 'gravityview_log_debug', __FUNCTION__ . ' $atts: ', $atts );

		return GravityView_frontend::getInstance()->render_view( $atts );
	}

}

new GravityView_Shortcode;