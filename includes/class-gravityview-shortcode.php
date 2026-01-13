<?php
/**
 * [gravityview] Shortcode class
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.13
 */

/**
 * Handle the [gravityview] shortcode
 *
 * @since 1.13
 * @deprecated since 2.0.3
 * @see \GV\Shortcodes\gravityview
 */
class GravityView_Shortcode {

	function __construct() {
		$this->add_hooks();
	}

	private function add_hooks() {
	}

	/**
	 * Callback function for add_shortcode()
	 *
	 * @since 1.13
	 *
	 * @static
	 * @param mixed       $passed_atts
	 * @param string|null $content Content passed inside the shortcode
	 * @return null|string If admin, null. Otherwise, output of $this->render_view()
	 */
	function shortcode( $passed_atts, $content = null ) {

		// Don't process when saving post.
		if ( is_admin() ) {
			return null;
		}

		gravityview()->log->debug( '$passed_atts: ', array( 'data' => $passed_atts ) );

		// Get details about the current View
		if ( ! empty( $passed_atts['detail'] ) ) {
			return $this->get_view_detail( $passed_atts['detail'] );
		}

		$atts = $this->parse_and_sanitize_atts( $passed_atts );

		return GravityView_frontend::getInstance()->render_view( $atts );
	}

	/**
	 * Validate attributes passed to the [gravityview] shortcode. Supports {get} Merge Tags values.
	 *
	 * Attributes passed to the shortcode are compared to registered attributes {@see \GV\View_Settings::defaults}
	 * Only attributes that are defined will be allowed through.
	 *
	 * Then, {get} merge tags are replaced with their $_GET values, if passed
	 *
	 * Then, attributes are sanitized based on the type of setting (number, checkbox, select, radio, text)
	 *
	 * @since 1.15.1
	 *
	 * @see \GV\View_Settings::defaults() Only attributes defined in default() are valid to be passed via the shortcode
	 *
	 * @param array $passed_atts Attribute pairs defined to render the View
	 *
	 * @return array Valid and sanitized attribute pairs
	 */
	private function parse_and_sanitize_atts( $passed_atts ) {

		$defaults = \GV\View_Settings::defaults( true );

		$supported_atts = array_fill_keys( array_keys( $defaults ), '' );

		// Whittle down the attributes to only valid pairs
		$filtered_atts = shortcode_atts( $supported_atts, $passed_atts, 'gravityview' );

		// Only keep the passed attributes after making sure that they're valid pairs
		$filtered_atts = array_intersect_key( (array) $passed_atts, $filtered_atts );

		$atts = array();

		foreach ( $filtered_atts as $key => $passed_value ) {

			// Allow using GravityView merge tags in shortcode attributes, like {get} and {created_by}
			$passed_value = GravityView_Merge_Tags::replace_variables( $passed_value );

			switch ( $defaults[ $key ]['type'] ) {

				/**
				 * Make sure number fields are numeric.
				 * Also, convert mixed number strings to numbers
				 *
				 * @see http://php.net/manual/en/function.is-numeric.php#107326
				 */
				case 'number':
					if ( is_numeric( $passed_value ) ) {
						$atts[ $key ] = ( $passed_value + 0 );
					}
					break;

				/** @since 2.1 */
				case 'operator':
					if ( GFFormsModel::is_valid_operator( $passed_value ) ) {
						$atts[ $key ] = $passed_value;
					}
					break;

				// Checkboxes should be 1 or 0
				case 'checkbox':
					$atts[ $key ] = gv_empty( $passed_value, true, false ) ? 0 : 1;
					break;

				/**
				 * Only allow values that are defined in the settings
				 */
				case 'select':
				case 'radio':
					$options = isset( $defaults[ $key ]['choices'] ) ? $defaults[ $key ]['choices'] : $defaults[ $key ]['options'];
					if ( in_array( $passed_value, array_keys( $options ) ) ) {
						$atts[ $key ] = $passed_value;
					}
					break;

				case 'text':
				default:
					$atts[ $key ] = $passed_value;
					break;
			}
		}

		return $atts;
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
		$return           = '';

		switch ( $detail ) {
			case 'total_entries':
				$return = number_format_i18n( $gravityview_view->getTotalEntries() );
				break;
			case 'first_entry':
				$paging = $gravityview_view->getPaginationCounts();
				$return = empty( $paging ) ? '' : number_format_i18n( \GV\Utils::get( $paging, 'first', 0 ) );
				break;
			case 'last_entry':
				$paging = $gravityview_view->getPaginationCounts();
				$return = empty( $paging ) ? '' : number_format_i18n( \GV\Utils::get( $paging, 'last', 0 ) );
				break;
			case 'page_size':
				$paging = $gravityview_view->getPaging();
				$return = number_format_i18n( \GV\Utils::get( $paging, 'page_size', 0 ) );
				break;
		}

		/**
		 * Filter the detail output returned from `[gravityview detail="$detail"]`.
		 *
		 * @since 1.13
		 *
		 * @param string $return Existing output.
		 */
		$return = apply_filters( 'gravityview/shortcode/detail/' . $detail, $return );

		return $return;
	}
}

new GravityView_Shortcode();
