<?php
/**
 * Functions that don't require GravityView or Gravity Forms API access but are used in the plugin to extend PHP and WP functions
 * @since 1.12
 */

/**
 * Check whether a variable is not an empty string
 *
 * @see /templates/fields/product.php Used to check whether the product array is empty or not
 *
 * @since 1.12
 *
 * @param mixed $mixed Variable to check
 *
 * @return bool true: $mixed is *not* an empty string; false: $mixed *is* an empty string
 */
function gravityview_is_not_empty_string( $mixed = '' ) {
	return ( $mixed !== '' );
}

/**
 * Get `get_permalink()` without the home_url() prepended to it.
 *
 * get_permalink() does a lot of good stuff: it gets the correct permalink structure for custom post types, pages,
 * posts, etc. Instead of using `?p={id}`, `?page_id={id}`, or `?p={id}&post_type={post_type}`, by using
 * get_permalink(), we can use `?p=slug` or `?gravityview={slug}`
 *
 * We could do this in a cleaner fashion, but this prevents a lot of code duplication, checking for URL structure, etc.
 *
 * @param int|WP_Post $id        Optional. Post ID or post object. Default current post.
 *
 * @return array URL args, if exists. Empty array if not.
 */
function gravityview_get_permalink_query_args( $id = 0 ) {

	$parsed_permalink = parse_url( get_permalink( $id ) );

	$permalink_args =  isset( $parsed_permalink['query'] ) ? $parsed_permalink['query'] : false;

	if( empty( $permalink_args ) ) {
		return array();
	}

	parse_str( $permalink_args, $args );

	return $args;
}

/**
 * sanitize_html_class doesn't handle spaces (multiple classes). We remedy that.
 * @uses sanitize_html_class
 * @param  string|array      $classes Text or arrray of classes to sanitize
 * @return string            Sanitized CSS string
 */
function gravityview_sanitize_html_class( $classes ) {

	if( is_string( $classes ) ) {
		$classes = explode(' ', $classes );
	}

	// If someone passes something not string or array, we get outta here.
	if( !is_array( $classes ) ) { return $classes; }

	$classes = array_map( 'sanitize_html_class' , $classes );

	return implode( ' ', $classes );
}

/**
 * Replace multiple newlines, tabs, and spaces with a single space
 *
 * @since 1.13
 *
 * @param string $string String to strip whitespace from
 *
 * @return string Stripped string!
 */
function gravityview_strip_whitespace( $string ) {
	$string = normalize_whitespace( $string );
	return preg_replace('/[\r\n\t ]+/', ' ', $string );
}

/**
 * Get the contents of a file using `include()` and `ob_start()`
 *
 * @since 1.13
 *
 * @param string $file_path Full path to a file
 *
 * @return string Included file contents
 */
function gravityview_ob_include( $file_path ) {
	if( ! file_exists( $file_path ) ) {
		do_action( 'gravityview_log_error', __FUNCTION__ . ': File path does not exist. ', $file_path );
		return '';
	}
	ob_start();
	include( $file_path );
	return ob_get_clean();
}

/**
 * Get an image of our intrepid explorer friend
 * @since 1.12
 * @return string HTML image tag with floaty's cute mug on it
 */
function gravityview_get_floaty() {

	if( function_exists('is_rtl') && is_rtl() ) {
		$style = 'margin:10px 10px 10px 0;';
		$class = 'alignright';
	} else {
		$style = 'margin:10px 10px 10px 0;';
		$class = 'alignleft';
	}

	return '<img src="'.plugins_url( 'assets/images/astronaut-200x263.png', GRAVITYVIEW_FILE ).'" class="'.$class.'" height="87" width="66" alt="The GravityView Astronaut Says:" style="'.$style.'" />';
}

/**
 * Intelligently format a number
 *
 * If you don't define the number of decimal places, then it will use the existing number of decimal places. This is done
 * in a way that respects the localization of the site.
 *
 * If you do define decimals, it uses number_format_i18n()
 *
 * @see number_format_i18n()
 *
 * @since 1.13
 *
 * @param int|float|string|double $number A number to format
 * @param int|string $decimals Optional. Precision of the number of decimal places. Default '' (use existing number of decimals)
 *
 * @return string Converted number in string format.
 */
function gravityview_number_format( $number, $decimals = '' ) {
	global $wp_locale;

	if( '' === $decimals ) {

		$decimal_point = isset( $wp_locale ) ? $wp_locale->number_format['decimal_point'] : '.';

		/**
		 * Calculate the position of the decimal point in the number
		 * @see http://stackoverflow.com/a/2430144/480856
		 */
		$decimals = strlen( substr( strrchr( $number, $decimal_point ), 1 ) );
	}

	$number = number_format_i18n( $number, (int)$decimals );

	return $number;
}