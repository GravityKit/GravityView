<?php
/**
 * Functions that don't require GravityView or Gravity Forms API access but are used in the plugin to extend PHP and WP functions
 * @since 1.12
 */


/**
 * Get the URL for a CSS file
 * 
 * If there's a CSS file with the same name as a GravityView CSS file in the current theme directory, it will be used.
 * Place the CSS file in a `/gravityview/css/` sub-directory. 
 * 
 * Example: /twentysixteen/gravityview/css/gv-default-styles.css
 *
 * Will use, in order:
 * 1) [theme directory]/gravityview/css/
 * 2) [gravityview plugin]/css/ (no check performed)
 *
 * @since 1.17
 *
 * @uses get_stylesheet_directory()
 * @uses get_stylesheet_directory_uri()
 *
 * @param string $css_file Filename of the CSS file (like gv-default-styles.css)
 * @param string $dir_path Absolute path to the directory where the CSS file is stored. If empty, uses default GravityView templates CSS folder.
 *
 * @return string URL path to the file.
 */
function gravityview_css_url( $css_file = '', $dir_path = '' ) {

	// If there's an overriding CSS file in the current template folder, use it.
	$template_css_path = trailingslashit( get_stylesheet_directory() ) . 'gravityview/css/' . $css_file;

	if( file_exists( $template_css_path ) ) {
		$path = trailingslashit( get_stylesheet_directory_uri() ) . 'gravityview/css/' . $css_file;
		do_action( 'gravityview_log_debug', __FUNCTION__ . ': Stylesheet override ('. esc_attr( $css_file ) .')' );
	} else {
		// Default: use GravityView CSS file

		// If no path is provided, assume default plugin templates CSS folder
		if( '' === $dir_path ) {
			$dir_path = GRAVITYVIEW_DIR . 'templates/css/';
		}
		
		// plugins_url() expects a path to a file, not directory. We append a file to be stripped.
		$path = plugins_url( $css_file, trailingslashit( $dir_path )  . 'stripped-by-plugin_basename.php' );
	}

	return $path;
}

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
 * Similar to the WordPress `selected()`, `checked()`, and `disabled()` functions, except it allows arrays to be passed as current value
 *
 * @see selected() WordPress core function
 *
 * @param string $value One of the values to compare
 * @param mixed $current (true) The other value to compare if not just true
 * @param bool $echo Whether to echo or just return the string
 * @param string $type The type of checked|selected|disabled we are doing
 *
 * @return string html attribute or empty string
 */
function gv_selected( $value, $current, $echo = true, $type = 'selected' ) {

	$output = '';
	if( is_array( $current ) ) {
		if( in_array( $value, $current ) ) {
			$output = __checked_selected_helper( true, true, false, $type );
		}
	} else {
		$output = __checked_selected_helper( $value, $current, false, $type );
	}

	if( $echo ) {
		echo $output;
	}

	return $output;
}


if( ! function_exists( 'gravityview_sanitize_html_class' ) ) {

	/**
	 * sanitize_html_class doesn't handle spaces (multiple classes). We remedy that.
	 *
	 * @uses sanitize_html_class
	 *
	 * @param  string|array $classes Text or array of classes to sanitize
	 *
	 * @return string            Sanitized CSS string
	 */
	function gravityview_sanitize_html_class( $classes ) {

		if ( is_string( $classes ) ) {
			$classes = explode( ' ', $classes );
		}

		// If someone passes something not string or array, we get outta here.
		if ( ! is_array( $classes ) ) {
			return $classes;
		}

		$classes = array_map( 'trim', $classes );
		$classes = array_map( 'sanitize_html_class', $classes );
		$classes = array_filter( $classes );

		return implode( ' ', $classes );
	}
}

/**
 * Replace multiple newlines, tabs, and spaces with a single space
 *
 * First, runs normalize_whitespace() on a string. This replaces multiple lines with a single line, and tabs with spaces.
 * We then strip any tabs or newlines and replace *those* with a single space.
 *
 * @see normalize_whitespace()
 * @see GravityView_Helper_Functions_Test::test_gravityview_strip_whitespace
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
 * @since 1.15 Added $object param
 *
 * @param string $file_path Full path to a file
 * @param mixed $object Pass pseudo-global to the included file
 * @return string Included file contents
 */
function gravityview_ob_include( $file_path, $object = NULL ) {
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
function gravityview_get_floaty( $height = 87 ) {

	$width = $height * 0.7586206897;

	if( function_exists('is_rtl') && is_rtl() ) {
		$style = 'margin:10px 10px 10px 0;';
		$class = 'alignright';
	} else {
		$style = 'margin:10px 10px 10px 0;';
		$class = 'alignleft';
	}

	return '<img src="'.plugins_url( 'assets/images/astronaut-200x263.png', GRAVITYVIEW_FILE ).'" class="'.$class.'" height="'.intval( $height ).'" width="'.round( $width, 2 ).'" alt="The GravityView Astronaut Says:" style="'.$style.'" />';
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

/**
 * Is the value empty?
 *
 * Allows you to pass a function instead of just a variable, like the empty() function insists upon (until PHP 5.5)
 *
 * Checks whether `false`, `null`, empty string, empty array, object with no vars defined
 *
 * @since 1.15.1
 * @param  mixed  $value Check whether this is empty
 * @param boolean $zero_is_empty Should the number zero be treated as an empty value?
 * @param boolean $allow_string_booleans Whether to check if 'yes', 'true' => `true` and 'no', 'false' => `false`
 * @return boolean        True: empty; false: not empty
 */
function gv_empty( $value, $zero_is_empty = true, $allow_string_booleans = true ) {

	/**
	 * Arrays with empty values are empty.
	 *
	 * Consider the a missing product field.
	 */
	if ( is_array( $value ) ) {
		$values = array();
		foreach ( $value as $v ) {
			if ( ! gv_empty( $v, $zero_is_empty, $allow_string_booleans ) ) {
				return false;
			}
		}
		return true;
	}

	if (
		! isset( $value ) // If it's not set, it's empty!
		|| false === $value
		|| null === $value
	    || '' === $value // Empty string
		|| array() === $value // Empty array
		|| ( is_object( $value ) && ! get_object_vars( $value ) ) // Empty object
	) {
		return true;
	}

	if ( is_string( $value ) && $allow_string_booleans ) {

		$value = trim( $value );
		$value = strtolower( $value );

		if ( in_array( $value, array( 'yes', 'true' ), true ) ) {
			$value = true;
		} else if( in_array( $value, array( 'no', 'false' ), true ) ) {
			$value = false;
		}
	}

	// If zero isn't empty, then if $value is a number and it's empty, it's zero. Thus, return false.
	if ( ! $zero_is_empty && is_numeric( $value ) && empty( $value ) ) {
		return false;
	}

	return empty( $value );
}


/**
 * Maps a function to all non-iterable elements of an array or an object.
 *
 * @see map_deep() This is an alias of the WP core function `map_deep()`, added in 4.4. Here for legacy purposes.
 * @since 1.16.3
 *
 * @param mixed    $value    The array, object, or scalar.
 * @param callable $callback The function to map onto $value.
 *
 * @return mixed The value with the callback applied to all non-arrays and non-objects inside it.
 */
function gv_map_deep( $value, $callback ) {

	// Use the original function, if exists.
	// Requires WP 4.4+
	if( function_exists( 'map_deep') ) {
		return map_deep( $value, $callback );
	}

	// Exact copy of map_deep() code below:
	if ( is_array( $value ) ) {
		foreach ( $value as $index => $item ) {
			$value[ $index ] = gv_map_deep( $item, $callback );
		}
	} elseif ( is_object( $value ) ) {
		$object_vars = get_object_vars( $value );
		foreach ( $object_vars as $property_name => $property_value ) {
			$value->$property_name = gv_map_deep( $property_value, $callback );
		}
	} else {
		$value = call_user_func( $callback, $value );
	}

	return $value;
}

/**
 * Check whether a string is a expected date format
 *
 * @since 1.15.2
 *
 * @param string $datetime The date to check
 * @param string $expected_format Check whether the date is formatted as expected. Default: Y-m-d
 *
 * @return bool True: it's a valid datetime, formatted as expected. False: it's not a date formatted as expected.
 */
function gravityview_is_valid_datetime( $datetime, $expected_format = 'Y-m-d' ) {

	/**
	 * @var bool|DateTime False if not a valid date, (like a relative date). DateTime if a date was created.
	 */
	$formatted_date = DateTime::createFromFormat( $expected_format, $datetime );

	/**
	 * @see http://stackoverflow.com/a/19271434/480856
	 */
	return ( $formatted_date && $formatted_date->format( $expected_format ) === $datetime );
}

/**
 * Very commonly needed: get the # of the input based on a full field ID.
 *
 * Example: 12.3 => field #12, input #3. Returns: 3
 * Example: 7 => field #7, no input. Returns: 0
 *
 * @since 1.16.4
 *
 * @param string $field_id Full ID of field, with or without input ID, like "12.3" or "7".
 *
 * @return int If field ID has an input, returns that input number. Otherwise, returns false.
 */
function gravityview_get_input_id_from_id( $field_id = '' ) {

	if ( ! is_numeric( $field_id ) ) {
		do_action( 'gravityview_log_error', __FUNCTION__ . ': $field_id not numeric', $field_id );
		return false;
	}

	$exploded = explode( '.', "{$field_id}" );

	return isset( $exploded[1] ) ? intval( $exploded[1] ) : false;
}

/**
 * Get categories formatted in a way used by GravityView and Gravity Forms input choices
 *
 * @since 1.15.3
 *
 * @see get_terms()
 *
 * @param array $args Arguments array as used by the get_terms() function. Filtered using `gravityview_get_terms_choices_args` filter. Defaults: { \n
 *   @type string $taxonomy Used as first argument in get_terms(). Default: "category"
 *   @type string $fields Default: 'id=>name' to only fetch term ID and Name \n
 *   @type int $number  Limit the total number of terms to fetch. Default: 1000 \n
 * }
 *
 * @return array Multidimensional array with `text` (Category Name) and `value` (Category ID) keys.
 */
function gravityview_get_terms_choices( $args = array() ) {

	$defaults = array(
		'type'         => 'post',
		'child_of'     => 0,
		'number'       => 1000, // Set a reasonable max limit
		'orderby'      => 'name',
		'order'        => 'ASC',
		'hide_empty'   => 0,
		'hierarchical' => 1,
		'taxonomy'     => 'category',
		'fields'       => 'id=>name',
	);

	$args = wp_parse_args( $args, $defaults );

	/**
	 * @filter `gravityview_get_terms_choices_args` Modify the arguments passed to `get_terms()`
	 * @see get_terms()
	 * @since 1.15.3
	 */
	$args = apply_filters( 'gravityview_get_terms_choices_args', $args );

	$terms = get_terms( $args['taxonomy'], $args );

	$choices = array();

	if ( is_array( $terms ) ) {
		foreach ( $terms as $term_id => $term_name ) {
			$choices[] = array(
				'text'  => $term_name,
				'value' => $term_id
			);
		}
	}

	return $choices;
}

/**
 * Maybe convert jQuery-serialized fields into array, otherwise return $_POST['fields'] array
 *
 * Fields are passed as a jQuery-serialized array, created in admin-views.js in the serializeForm method.
 *
 * @since 1.16.5
 *
 * @uses GVCommon::gv_parse_str
 *
 * @return array Array of fields
 */
function _gravityview_process_posted_fields() {
	$fields = array();

	if( !empty( $_POST['gv_fields'] ) ) {
		if ( ! is_array( $_POST['gv_fields'] ) ) {

			// We are not using parse_str() due to max_input_vars limitation with large View configurations
			$fields_holder = array();
			GVCommon::gv_parse_str( $_POST['gv_fields'], $fields_holder );

			if ( isset( $fields_holder['fields'] ) ) {
				$fields = $fields_holder['fields'];
			} else {
				do_action( 'gravityview_log_error', '[save_postdata] No `fields` key was found after parsing $fields string', $fields_holder );
			}

		} else {
			$fields = $_POST['gv_fields'];
		}
	}

	return $fields;
}
