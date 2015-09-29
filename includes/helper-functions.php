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


/**
 * Convert a whole link into a shorter link for display
 *
 * @since 1.1
 *
 * @param  string $value Existing URL
 * @return string        If parse_url doesn't find a 'host', returns original value. Otherwise, returns formatted link.
 */
function gravityview_format_link( $value = null ) {


	$parts = parse_url( $value );

	// No domain? Strange...show the original text.
	if( empty( $parts['host'] ) ) {
		return $value;
	}

	// Start with empty value for the return URL
	$return = '';

	/**
	 * @filter `gravityview_anchor_text_striphttp` Strip scheme from the displayed URL?
	 * @since 1.5.1
	 * @param boolean $enable Whether to strip the scheme. Return false to show scheme. (default: true)\n
	 * If true: `http://example.com => example.com`
	 */
	if( false === apply_filters('gravityview_anchor_text_striphttp', true) ) {

		if( isset( $parts['scheme'] ) ) {
			$return .= $parts['scheme'];
		}

	}

	// The domain, which may contain a subdomain
	$domain = $parts['host'];

	/**
	 * @filter `gravityview_anchor_text_stripwww` Strip www from the domain?
	 * @since 1.5.1
	 * @param boolean $enable Whether to strip www. Return false to show www. (default: true)\n
	 * If true: `www.example.com => example.com`
	 */
	$strip_www = apply_filters('gravityview_anchor_text_stripwww', true );

	if( $strip_www ) {
		$domain = str_replace('www.', '', $domain );
	}

	/**
	 * @filter `gravityview_anchor_text_nosubdomain` Strip subdomains from the domain?
	 * @since 1.5.1
	 * @param boolean $enable Whether to strip subdomains. Return false to show subdomains. (default: true)\n
	 * If true: `http://demo.example.com => example.com` \n
	 * If false: `http://demo.example.com => demo.example.com`
	 */
	$strip_subdomains = apply_filters('gravityview_anchor_text_nosubdomain', true);

	if( $strip_subdomains ) {

		$domain = _gravityview_strip_subdomain( $parts['host'] );

	}

	// Add the domain
	$return .= $domain;

	/**
	 * @filter `gravityview_anchor_text_rootonly` Display link path going only to the base directory, not a sub-directory or file?
	 * @since 1.5.1
	 * @param boolean $enable Whether to enable "root only". Return false to show full path. (default: true)\n
	 * If true: `http://example.com/sub/directory/page.html => example.com`  \n
	 * If false: `http://example.com/sub/directory/page.html => example.com/sub/directory/page.html`
	 */
	$root_only = apply_filters('gravityview_anchor_text_rootonly', true);

	if( empty( $root_only ) ) {

		if( isset( $parts['path'] ) ) {
			$return .= $parts['path'];
		}
	}

	/**
	 * @filter `gravityview_anchor_text_noquerystring` Strip the query string from the end of the URL?
	 * @since 1.5.1
	 * @param boolean $enable Whether to enable "root only". Return false to show full path. (default: true)\n
	 * If true: `http://example.com/?query=example => example.com`
	 */
	$strip_query_string = apply_filters('gravityview_anchor_text_noquerystring', true );

	if( empty( $strip_query_string ) ) {

		if( isset( $parts['query'] ) ) {
			$return .= '?'.$parts['query'];
		}

	}

	return $return;
}

/**
 * Do a _very_ basic match for second-level TLD domains, like `.co.uk`
 *
 * Ideally, we'd use https://github.com/jeremykendall/php-domain-parser to check for this, but it's too much work for such a basic functionality. Maybe if it's needed more in the future. So instead, we use [Basic matching regex](http://stackoverflow.com/a/12372310).
 * @param  string $domain Domain to check if it's a TLD or subdomain
 * @return string         Extracted domain if it has a subdomain
 */
function _gravityview_strip_subdomain( $string_maybe_has_subdomain ) {

	if( preg_match("/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.(?:com\.|co\.|net\.|org\.|firm\.|me\.|school\.|law\.|gov\.|mod\.|msk\.|irkutsks\.|sa\.|act\.|police\.|plc\.|ac\.|tm\.|asso\.|biz\.|pro\.|cg\.|telememo\.)?[a-z\.]{2,6})$/i", $string_maybe_has_subdomain, $matches ) ) {
		return $matches['domain'];
	} else {
		return $string_maybe_has_subdomain;
	}
}