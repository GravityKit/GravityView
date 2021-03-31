<?php

/**
 * Enhance Gravity Forms' merge tag functionality by adding additional merge tags
 * @since 1.8.4
 */
class GravityView_Merge_Tags {

	/**
	 * @since 1.8.4
	 */
	public function __construct() {
		$this->add_hooks();
	}

	/**
	 * Tap in to gform_replace_merge_tags to add merge tags
	 * @since 1.8.4
	 */
	private function add_hooks() {

		/** @see GFCommon::replace_variables_prepopulate **/
		add_filter( 'gform_replace_merge_tags', array( 'GravityView_Merge_Tags', 'replace_gv_merge_tags' ), 10, 7 );

		// Process after 10 priority
		add_filter( 'gform_merge_tag_filter', array( 'GravityView_Merge_Tags', 'process_modifiers' ), 20, 5 );

	}

	/**
	 * Process custom GravityView modifiers for Merge Tags
	 *
	 * Is not processed on `{all_fields}` Merge Tag.
	 *
	 * @since 1.17
	 *
	 * @param string $value The current merge tag value to be filtered.
	 * @param string $merge_tag If the merge tag being executed is an individual field merge tag (i.e. {Name:3}), this variable will contain the field's ID. If not, this variable will contain the name of the merge tag (i.e. all_fields).
	 * @param string $modifier The string containing any modifiers for this merge tag. For example, "maxwords:10" would be the modifiers for the following merge tag: `{Text:2:maxwords:10}`.
	 * @param GF_Field $field The current field.
	 * @param mixed $raw_value The raw value submitted for this field.
	 *
	 * @return string If no modifiers passed, $raw_value is not a string, or {all_fields} Merge Tag is used, original value. Otherwise, output from modifier methods.
	 */
	public static function process_modifiers( $value, $merge_tag, $modifier, $field, $raw_value ) {

		// No modifier was set or the raw value was empty
		if ( 'all_fields' === $merge_tag || '' === $modifier || ! is_string( $raw_value ) || '' === $raw_value ) {
			return $value;
		}

		// matching regex => the value is the method to call to replace the value.
		$gv_modifiers = array(
			'maxwords:(\d+)' => 'modifier_maxwords', /** @see modifier_maxwords */
		    'timestamp' => 'modifier_timestamp', /** @see modifier_timestamp */
			'explode' => 'modifier_explode', /** @see modifier_explode */

			/** @see modifier_strings */
			'urlencode' => 'modifier_strings',
			'wpautop' => 'modifier_strings',
		    'esc_html' => 'modifier_strings',
		    'sanitize_html_class' => 'modifier_strings',
			'sanitize_title' => 'modifier_strings',
			'strtolower' => 'modifier_strings',
			'strtoupper' => 'modifier_strings',
			'ucfirst' => 'modifier_strings',
			'ucwords' => 'modifier_strings',
			'wptexturize' => 'modifier_strings',
		);

		$modifiers = explode( ',', $modifier );

		$return = $raw_value;

		$unserialized = maybe_unserialize( $raw_value );

		if ( method_exists( $field, 'get_value_merge_tag' ) && is_array( $unserialized ) ) {

			$non_gv_modifiers = array_diff( $modifiers, array_keys( $gv_modifiers ) );

			$return = $field->get_value_merge_tag( $value, '', array( 'currency' => '' ), array(), implode( '', $non_gv_modifiers ), $raw_value, false, false, 'text', false);
		}

		foreach ( $modifiers as $passed_modifier ) {

			foreach( $gv_modifiers as $gv_modifier => $method ) {

				// Uses ^ to only match the first modifier, to enforce same order as passed by GF
				preg_match( '/^' . $gv_modifier . '/ism', $passed_modifier, $matches );

				if ( empty( $matches ) ) {
					continue;
				}

				// The called method is passed the raw value and the full matches array
				$return = self::$method( $return, $matches, $value, $field );
				break;
			}
		}

		// No GravityView modifications were made; return the (default) original value
		if ( $raw_value === $return ) {
			return $value;
		}

		/**
		 * @filter `gravityview/merge_tags/modifiers/value` Modify the merge tag modifier output
		 * @since 2.0
		 * @param string $return The current merge tag value to be filtered.
		 * @param string $raw_value The raw value submitted for this field. May be CSV or JSON-encoded.
		 * @param string $value The original merge tag value, passed from Gravity Forms
		 * @param string $merge_tag If the merge tag being executed is an individual field merge tag (i.e. {Name:3}), this variable will contain the field's ID. If not, this variable will contain the name of the merge tag (i.e. all_fields).
		 * @param string $modifier The string containing any modifiers for this merge tag. For example, "maxwords:10" would be the modifiers for the following merge tag: `{Text:2:maxwords:10}`.
		 * @param GF_Field $field The current field.
		 */
		$return = apply_filters( 'gravityview/merge_tags/modifiers/value', $return, $raw_value, $value, $merge_tag, $modifier, $field );

		return $return;
	}

	/**
	 * Convert Date field values to timestamp int
	 *
	 * @since 1.17
	 *
	 * @uses strtotime()
	 *
	 * @param string $raw_value Value to filter
	 * @param array $matches Regex matches group
	 *
	 * @return int Timestamp value of date. `-1` if not a valid timestamp.
	 */
	private static function modifier_timestamp( $raw_value, $matches ) {

		if( empty( $matches[0] ) ) {
			return $raw_value;
		}

		$timestamp = strtotime( $raw_value );

		// Can return false or -1, depending on PHP version.
		return ( $timestamp && $timestamp > 0 ) ? $timestamp : -1;
	}

	/**
	 * Trim the Merge Tag's length in words.
	 *
	 * Notes:
	 * - HTML tags are preserved
	 * - HTML entities are encoded, but if they are separated by word breaks, they will be counted as words
	 *   Example: "one & two" will be counted as three words, but "one& two" will be counted as two words
	 *
	 * @since 1.17
	 * @since 2.0 Added $field param and support for urlencode
	 *
	 * @param string $raw_value Value to filter
	 * @param array $matches Regex matches group
	 * @param GF_Field|false $field
	 *
	 * @return string Modified value, if longer than the passed `maxwords` modifier
	 */
	private static function modifier_maxwords( $raw_value, $matches, $field = null ) {

		if( ! is_string( $raw_value ) || empty( $matches[1] ) || ! function_exists( 'wp_trim_words' ) ) {
			return $raw_value;
		}

		$max = intval( $matches[1] );

		$more_placeholder = '[GVMORE]';

		/**
		 * Use htmlentities instead, so that entities are double-encoded, and decoding restores original values.
		 * @see https://core.trac.wordpress.org/ticket/29533#comment:3
		 */
		$return = force_balance_tags( wp_specialchars_decode( wp_trim_words( htmlentities( $raw_value ), $max, $more_placeholder ) ) );

		$return = str_replace( $more_placeholder, '&hellip;', $return );

		return self::maybe_urlencode( $field, $return );
	}

	/**
	 * GF 2.3 adds GF_Field::get_modifers(), which allows us to check if a field has urlencode applied to it
	 *
	 * @since 2.0
	 *
	 * @see GFCommon::replace_field_variable
	 *
	 * Here's the relevant code:
	 *
	 * <code>
	 * $modifier  = strtolower( rgar( $match, $i ) );
	 * $modifiers = array_map( 'trim', explode( ',', $modifier ) );
	 * $field->set_modifiers( $modifiers );
	 * </code>
	 *
	 * @param GF_Field|false $field
	 * @param string $value
	 *
	 * @return mixed|string
	 */
	private static function maybe_urlencode( $field = false, $value = '' ) {

		$return = $value;

		if ( $field && method_exists( $field, 'get_modifiers' ) ) {

			$modifiers = $field->get_modifiers();

			if ( in_array( 'urlencode', $modifiers ) ) {
				$return = urlencode( $return );
			}
		}

		return $return;
	}


	/**
	 * Convert JSON or CSV values into space-separated string
	 *
	 * Useful for Multiple Select field data, like categories
	 *
	 * @since 2.0
	 *
	 * @param mixed $raw_value The raw value submitted for this field. May be CSV or JSON-encoded.
	 * @param array $matches Regex matches group
	 * @param string $value The value as passed by Gravity Forms
	 * @param GF_Field|false $field Gravity Forms field, if any
	 *
	 * @return string
	 */
	private static function modifier_explode( $raw_value, $matches, $value, $field = null ) {

		// For JSON-encoded arrays
		if( $json_array = json_decode( $raw_value, true ) ) {
			return implode( ' ', $json_array );
		}

		return implode( ' ', explode( ',', $raw_value ) );
	}

	/**
	 * Process strings with common PHP string manipulations
	 *
	 * @since 2.0
	 *
	 * @param mixed $raw_value The raw value submitted for this field. May be CSV or JSON-encoded.
	 * @param array $matches Regex matches group
	 * @param string $value The value as passed by Gravity Forms
	 * @param GF_Field|false $field Gravity Forms field, if any
	 *
	 * @return string
	 */
	private static function modifier_strings( $raw_value, $matches, $value = '', $field = null ) {

		if( empty( $matches[0] ) ) {
			return $raw_value;
		}

		$return = $raw_value;

		switch( $matches[0] ) {
			case 'urlencode':
				$return = urlencode( $raw_value );
				break;
			case 'wpautop':
				$return = trim( wpautop( $raw_value ) );
				break;
			case 'esc_html':
				$return = esc_html( $raw_value );
				break;
			case 'sanitize_html_class':
				$return = function_exists( 'gravityview_sanitize_html_class' ) ? gravityview_sanitize_html_class( $raw_value ) : sanitize_html_class( $raw_value );
				break;
			case 'sanitize_title':
				$return = sanitize_title( $raw_value, '', 'gravityview/merge-tags/modifier' );
				break;
			case 'strtoupper':
				$return = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $raw_value ) : strtoupper( $raw_value );
				break;
			case 'strtolower':
				$return = function_exists( 'mb_strtolower' ) ? mb_strtolower( $raw_value ) : strtolower( $raw_value );
				break;
			case 'ucwords':
				$return = ucwords( $raw_value );
				break;
			case 'ucfirst':
				$return = ucfirst( $raw_value );
				break;
			case 'wptexturize':
				$return = wptexturize( $raw_value );
				break;
		}

		return $return;
	}

	/**
	 * Alias for GFCommon::replace_variables()
	 *
	 * Before 1.15.3, it would check for merge tags before passing to Gravity Forms to improve speed.
	 *
	 * @since 1.15.3 - Now that Gravity Forms added speed improvements in 1.9.15, it's more of an alias with a filter
	 * to disable or enable replacements.
	 * @since 1.8.4 - Moved to GravityView_Merge_Tags
	 * @since 1.15.1 - Add support for $url_encode and $esc_html arguments
	 * @since 1.22.4 - Added $nl2br, $format, $aux_data args
	 *
	 * @param  string           $text         Text to replace variables in.
	 * @param  array            $form         GF Form array
	 * @param  array            $entry        GF Entry array
	 * @param  bool             $url_encode   Pass return value through `url_encode()`
	 * @param  bool             $esc_html     Pass return value through `esc_html()`
	 * @param  bool             $nl2br        Convert newlines to <br> HTML tags
	 * @param  string           $format       The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param  array            $aux_data     Additional data to be used to replace merge tags {@see https://www.gravityhelp.com/documentation/article/gform_merge_tag_data/}
	 * @return string           Text with variables maybe replaced
	 */
	public static function replace_variables( $text, $form = array(), $entry = array(), $url_encode = false, $esc_html = true, $nl2br = true, $format = 'html', $aux_data = array() ) {

		if ( ! is_string( $text ) ) {
			gravityview()->log->notice( '$text is not a string.', array( 'data' => $text ) );
			return $text;
		}

		/**
		 * @filter `gravityview_do_replace_variables` Turn off merge tag variable replacements.\n
		 * Useful where you want to process variables yourself. We do this in the Math Extension.
		 * @since 1.13
		 *
		 * @param[in,out] boolean $do_replace_variables True: yes, replace variables for this text; False: do not replace variables.
		 * @param[in] string $text       Text to replace variables in
		 * @param[in]  array      $form        GF Form array
		 * @param[in]  array      $entry        GF Entry array
		 */
		$do_replace_variables = apply_filters( 'gravityview/merge_tags/do_replace_variables', true, $text, $form, $entry );

		if ( strpos( $text, '{' ) === false || ! $do_replace_variables ) {
			return $text;
		}

		/**
		 * Make sure the required keys are set for GFCommon::replace_variables
		 *
		 * @internal Reported to GF Support on 12/3/2016
		 * @internal Fixed $form['title'] in Gravity Forms
		 * @see      https://github.com/gravityforms/gravityforms/pull/27/files
		 */
		$form['title']  = isset( $form['title'] ) ? $form['title'] : '';
		$form['id']     = isset( $form['id'] ) ? $form['id'] : '';
		$form['fields'] = isset( $form['fields'] ) ? $form['fields'] : array();

		return GFCommon::replace_variables( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format, $aux_data );
	}

	/**
	 * Run GravityView filters when using GFCommon::replace_variables()
	 *
	 * Instead of adding multiple hooks, add all hooks into this one method to improve speed
	 *
	 * @since 1.8.4
	 *
	 * @param string $text Text to replace
	 * @param array|bool $form Gravity Forms form array. When called inside {@see GFCommon::replace_variables()} (now deprecated), `false`
	 * @param array|bool $entry Entry array.  When called inside {@see GFCommon::replace_variables()} (now deprecated), `false`
	 * @param bool $url_encode Whether to URL-encode output
	 * @param bool $esc_html Whether to apply `esc_html()` to output
	 *
	 * @return mixed
	 */
	public static function replace_gv_merge_tags( $text, $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		if ( '' === $text ) {
			return $text;
		}

		/**
		 * This prevents the gform_replace_merge_tags filter from being called twice, as defined in:
		 * @see GFCommon::replace_variables()
		 * @see GFCommon::replace_variables_prepopulate()
		 * @todo Remove eventually: Gravity Forms fixed this issue in 1.9.14
		 */
		if ( false === $form ) {
			return $text;
		}

		$text = self::replace_site_url( $text, $form, $entry, $url_encode, $esc_html );

		$text = self::replace_get_variables( $text, $form, $entry, $url_encode );

		$text = self::replace_current_post( $text, $form, $entry, $url_encode, $esc_html );

		$text = self::replace_entry_link( $text, $form, $entry, $url_encode, $esc_html );

		return $text;
	}

	/**
	 * Add a {site_url} Merge Tag
	 *
	 * @since 2.10.1
	 *
	 * @param string $original_text Text to replace
	 * @param array $form Gravity Forms form array
	 * @param array $entry Entry array
	 * @param bool $url_encode Whether to URL-encode output
	 * @param bool $esc_html Indicates if the esc_html function should be applied.
	 *
	 * @return string Original text, if no {site_url} Merge Tags found, otherwise text with Merge Tag replaced
	 */
	public static function replace_site_url( $original_text, $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		if ( false === strpos( $original_text, '{site_url}' ) ) {
			return $original_text;
		}

		$site_url = get_site_url();

		if( $url_encode ) {
			$site_url = urlencode( $site_url );
		}

		if ( $esc_html ) {
			$site_url = esc_html( $site_url );
		}

		return str_replace( '{site_url}', $site_url, $original_text );
	}

	/**
	 * Add a {gv_entry_link} Merge Tag, alias of [gv_entry_link] shortcode in {gv_entry_link:[post id]:[action]} format
	 *
	 * @param string $original_text Text to replace
	 * @param array $form Gravity Forms form array
	 * @param array $entry Entry array
	 * @param bool $url_encode Whether to URL-encode output
	 * @param bool $esc_html Indicates if the esc_html function should be applied.
	 *
	 * @return string Original text, if no {gv_entry_link} Merge Tags found, otherwise text with Merge Tags replaced
	 */
	public static function replace_entry_link( $original_text, $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		// Is there is {gv_entry_link} or {gv_entry_link:[post id]} or {gv_entry_link:[post id]:[action]} merge tag?
		preg_match_all( "/{gv_entry_link(?:\:(\d+)\:?(.*?))?}/ism", $original_text, $matches, PREG_SET_ORDER );

		if( empty( $matches ) ) {
			return $original_text;
		}

		if ( ! class_exists( 'GravityView_Entry_Link_Shortcode' ) ) {
			gravityview()->log->error( 'GravityView_Entry_Link_Shortcode not found' );
			return $original_text;
		}

		$Shortcode = new GravityView_Entry_Link_Shortcode;

		$return = $original_text;

		/**
		 * @param array $match {
		 *   $match[0] Full tag
		 *   $match[1] Post ID (optional)
		 *   $match[2] Action (optional)
		 * }
		 */
		foreach ( $matches as $match ) {
			$full_tag = $match[0];

			$link_args = array(
				'return' => 'url',
				'entry_id' => $entry['id'],
				'post_id' => \GV\Utils::get( $match, 1, null ),
				'action' => \GV\Utils::get( $match, 2, 'read' ),
			);

			$entry_link = $Shortcode->read_shortcode( $link_args, null, 'gv_entry_link_merge_tag' );

			if( $url_encode ) {
				$entry_link = urlencode( $entry_link );
			}

			if ( $esc_html ) {
				$entry_link = esc_html( $entry_link );
			}

			$return = str_replace( $full_tag, $entry_link, $return );
		}

		return $return;
	}

	/**
	 * Format Merge Tags using GVCommon::format_date()
	 *
	 * @uses GVCommon::format_date()
	 *
	 * @see https://docs.gravityview.co/article/331-date-created-merge-tag for documentation
	 *
	 * @param string $date_created The Gravity Forms date created format
	 * @param string $property Any modifiers for the merge tag (`human`, `format:m/d/Y`)
	 *
	 * @return int|string If timestamp requested, timestamp int. Otherwise, string output.
	 */
	public static function format_date( $date_created = '', $property = '' ) {

		// Expand all modifiers, skipping escaped colons. str_replace worked better than preg_split( "/(?<!\\):/" )
		$exploded = explode( ':', str_replace( '\:', '|COLON|', $property ) );

		$atts = array(
			'format' => self::get_format_from_modifiers( $exploded, false ),
		    'human' => in_array( 'human', $exploded ), // {date_created:human}
			'diff' => in_array( 'diff', $exploded ), // {date_created:diff}
			'raw' => in_array( 'raw', $exploded ), // {date_created:raw}
			'timestamp' => in_array( 'timestamp', $exploded ), // {date_created:timestamp}
			'time' => in_array( 'time', $exploded ),  // {date_created:time}
		);

		$formatted_date = GVCommon::format_date( $date_created, $atts );

		return $formatted_date;
	}

	/**
	 * If there is a `:format` modifier in a merge tag, grab the formatting
	 *
	 * The `:format` modifier should always have the format follow it; it's the next item in the array
	 * In `foo:format:bar`, "bar" will be the returned format
	 *
	 * @since 1.16
	 *
	 * @param array $exploded Array of modifiers with a possible `format` value
	 * @param string $backup The backup value to use, if not found
	 *
	 * @return string If format is found, the passed format. Otherwise, the backup.
	 */
	private static function get_format_from_modifiers( $exploded, $backup = '' ) {

		$return = $backup;

		$format_key_index = array_search( 'format', $exploded );

		// If there's a "format:[php date format string]" date format, grab it
		if ( false !== $format_key_index && isset( $exploded[ $format_key_index + 1 ] ) ) {
			// Return escaped colons placeholder
			$return = str_replace( '|COLON|', ':', $exploded[ $format_key_index + 1 ] );
		}

		return $return;
	}

	/**
	 * Add a {current_post} Merge Tag for information about the current post (in the loop or singular)
	 *
	 * {current_post} is replaced with the current post's permalink by default, when no modifiers are passed.
	 * Pass WP_Post properties as :modifiers to access.
	 *
	 * {current_post} is the same as {embed_post}, except:
	 *
	 * - Adds support for {current_post:permalink}
	 * - Works on post archives, as well as singular
	 *
	 * @see https://www.gravityhelp.com/documentation/article/merge-tags/#embed-post for examples
	 * @see GFCommon::replace_variables_prepopulate - Code is there for {custom_field} and {embed_post} Merge Tags
	 *
	 * @param string $original_text Text to replace
	 * @param array $form Gravity Forms form array
	 * @param array $entry Entry array
	 * @param bool $url_encode Whether to URL-encode output
	 * @param bool $esc_html Indicates if the esc_html function should be applied.
	 *
	 * @return string Original text, if no {current_post} Merge Tags found, otherwise text with Merge Tags replaced
	 */
	public static function replace_current_post( $original_text, $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		$return = $original_text;

		// Is there a {current_post} or {current_post:[xyz]} merge tag?
		preg_match_all( "/{current_post(:(.*?))?}/ism", $original_text, $matches, PREG_SET_ORDER );

		// If there are no matches OR the Entry `created_by` isn't set or is 0 (no user)
		if ( empty( $matches ) ) {
			return $original_text;
		}

		$current_post = get_post();

		// WP_Error, arrays and NULL aren't welcome here.
		if ( ! $current_post || ! is_a( $current_post, 'WP_Post' ) ) {
			return $original_text;
		}

		foreach ( (array) $matches as $match ) {
			$full_tag = $match[0];
			$modifier = \GV\Utils::get( $match, 2, 'permalink' );

			$replacement = false;

			if ( 'permalink' === $modifier ) {
				$replacement = get_permalink( $current_post );
			} elseif ( isset( $current_post->{$modifier} ) ) {
				/** @see WP_Post Post properties */
				$replacement = $current_post->{$modifier};
			}

			if ( $replacement ) {

				if ( $esc_html ) {
					$replacement = esc_html( $replacement );
				}

				if( $url_encode ) {
					$replacement = urlencode( $replacement );
				}

				$return = str_replace( $full_tag, $replacement, $return );
			}
		}

		return $return;
	}

	/**
	 * Allow passing variables via URL to be displayed in Merge Tags
	 *
	 * Works with `[gvlogic]`:
	 *     [gvlogic if="{get:example}" is="false"]
	 *          ?example=false
	 *	   [else]
	 *	        ?example wasn't "false". It's {get:example}!
	 *     [/gvlogic]
	 *
	 * Supports passing arrays:
	 *     URL: `example[]=Example+One&example[]=Example+(with+comma)%2C+Two`
	 *     Merge Tag: `{get:example}`
	 *     Output: `Example One, Example (with comma), Two`
	 *
	 * @since 1.15
	 * @param string $text Text to replace
	 * @param array $form Gravity Forms form array
	 * @param array $entry Entry array
	 * @param bool $url_encode Whether to URL-encode output
	 *
	 * @return string Original text, if no Merge Tags found, otherwise text with Merge Tags replaced
	 */
	public static function replace_get_variables( $text, $form = array(), $entry = array(), $url_encode = false ) {

		// Is there is {get:[xyz]} merge tag?
		preg_match_all( "/{get:(.*?)}/ism", $text, $matches, PREG_SET_ORDER );

		// If there are no matches OR the Entry `created_by` isn't set or is 0 (no user)
		if( empty( $matches ) ) {
			return $text;
		}

		foreach ( $matches as $match ) {

			$full_tag = $match[0];
			$property = $match[1];

			$value = stripslashes_deep( \GV\Utils::_GET( $property ) );

			/**
			 * @filter `gravityview/merge_tags/get/glue/` Modify the glue used to convert an array of `{get}` values from an array to string
			 * @since 1.15
			 * @param[in,out] string $glue String used to `implode()` $_GET values Default: ', '
			 * @param[in] string $property The current name of the $_GET parameter being combined
			 */
			$glue = apply_filters( 'gravityview/merge_tags/get/glue/', ', ', $property );

			$value = is_array( $value ) ? implode( $glue, $value ) : $value;

			$value = $url_encode ? urlencode( $value ) : $value;

			/**
			 * @filter `gravityview/merge_tags/get/esc_html/{url parameter name}` Disable esc_html() from running on `{get}` merge tag
			 * By default, all values passed through URLs will be escaped for security reasons. If for some reason you want to
			 * pass HTML in the URL, for example, you will need to return false on this filter. It is strongly recommended that you do
			 * not disable this filter.
			 * @since 1.15
			 * @param bool $esc_html Whether to esc_html() the value. Default: `true`
			 */
			$esc_html = apply_filters('gravityview/merge_tags/get/esc_html/' . $property, true );

			$value = $esc_html ? esc_html( $value ) : $value;

			/**
			 * @filter `gravityview/merge_tags/get/esc_html/{url parameter name}` Modify the value of the `{get}` replacement before being used
			 * @param[in,out] string $value Value that will replace `{get}`
			 * @param[in] string $text Text that contains `{get}` (before replacement)
			 * @param[in] array $form Gravity Forms form array
			 * @param[in] array $entry Entry array
			 */
			$value = apply_filters('gravityview/merge_tags/get/value/' . $property, $value, $text, $form, $entry );

			$text = str_replace( $full_tag, $value, $text );
		}

		unset( $value, $glue, $matches );

		return $text;
	}
}

new GravityView_Merge_Tags;
