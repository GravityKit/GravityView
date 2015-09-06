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

		// Get details about the current View
		if( !empty( $atts['detail'] ) ) {
			return $this->get_view_detail( $atts['detail'] );
		}

		return GravityView_frontend::getInstance()->render_view( $atts );
	}


	/**
	 * Display details for the current View
	 *
	 * @since 1.13
	 *
	 * @param string $detail The information requested about the current View. Accepts `total_entries`, `first_entry` (entry #), `last_entry` (entry #), and `page_size`
	 *
	 * @return string Detail information
	 */
	private function get_view_detail( $detail = '' ) {

		$gravityview_view = GravityView_View::getInstance();
		$return = '';

		switch( $detail ) {
			case 'total_entries':
				$return = number_format_i18n( $gravityview_view->getTotalEntries() );
				break;
			case 'first_entry':
				$paging = $gravityview_view->getPaginationCounts();
				$return = empty( $paging ) ? '' : number_format_i18n( $paging['first'] );
				break;
			case 'last_entry':
				$paging = $gravityview_view->getPaginationCounts();
				$return = empty( $paging ) ? '' : number_format_i18n( $paging['last'] );
				break;
			case 'page_size':
				$paging = $gravityview_view->getPaging();
				$return = number_format_i18n( $paging['page_size'] );
				break;
		}

		/**
		 * @filter `gravityview/shortcode/detail/{$detail}` Filter the detail output returned from `[gravityview detail="$detail"]`
		 * @since 1.13
		 * @param string $return Existing output
		 */
		$return = apply_filters( 'gravityview/shortcode/detail/' . $detail, $return );

		return $return;
	}
}

new GravityView_Shortcode;