<?php

/**
 * Enhance Gravity Forms' merge tag functionality by adding additional merge tags
 *
 * @since 1.8.4
 */
class GravityView_Merge_Tags {
	/**
	 * Microcache for merge tag modifiers.
	 *
	 * @since 2.26
	 *
	 * @var array[]
	 */
	private static $merge_tag_modifiers = [];

	/**
	 * @since 1.8.4
	 */
	public function __construct() {
		$this->add_hooks();
	}

	/**
	 * Tap in to gform_replace_merge_tags to add merge tags
	 *
	 * @since 1.8.4
	 */
	private function add_hooks() {
		/** @see GFCommon::replace_variables_prepopulate */
		add_filter( 'gform_replace_merge_tags', array( 'GravityView_Merge_Tags', 'replace_gv_merge_tags' ), 10, 7 );

		// Process after 10 priority
		add_filter( 'gform_merge_tag_filter', array( 'GravityView_Merge_Tags', 'process_modifiers' ), 20, 5 );

		add_filter( 'gform_pre_replace_merge_tags', [ $this, 'cache_merge_tag_modifiers' ] );
	}

	/**
	 * Caches merge tag modifiers to preserve their case sensitivity.
	 * This is necessary because {@see GFCommon::replace_field_variable()) applies
	 * `strtolower()` to the modifier and causes issues where case is expected,
	 * such as date formatting (e.g., `format:Y-m-d`).
	 *
	 * @since 2.26
	 *
	 * @param string $text Text with merge tags.
	 *
	 * @return string
	 */
	public function cache_merge_tag_modifiers( $text ) {
		// Regex pattern taken from GFCommon::replace_variables().
		preg_match_all( '/{[^{]*?:(\d+(\.\w+)?)(:(.*?))?}/mi', $text, $matches, PREG_SET_ORDER );

		if ( ! $matches ) {
			return $text;
		}

		foreach ( $matches as $match ) {
			$modifier = $match[4] ?? '';

			if ( $modifier ) {
				self::$merge_tag_modifiers[ strtolower( $modifier ) ] = $modifier;
			}
		}

		return $text;
	}

	/**
	 * Process custom GravityView modifiers for Merge Tags
	 *
	 * Is not processed on `{all_fields}` Merge Tag.
	 *
	 * @since 1.17
	 *
	 * @param string   $value The current merge tag value to be filtered.
	 * @param string   $merge_tag If the merge tag being executed is an individual field merge tag (i.e. {Name:3}), this variable will contain the field's ID. If not, this variable will contain the name of the merge tag (i.e. all_fields).
	 * @param string   $modifier The string containing any modifiers for this merge tag. For example, "maxwords:10" would be the modifiers for the following merge tag: `{Text:2:maxwords:10}`.
	 * @param GF_Field $field The current field.
	 * @param mixed    $raw_value The raw value submitted for this field.
	 *
	 * @return string If no modifiers passed, $raw_value is not a string, or {all_fields} Merge Tag is used, original value. Otherwise, output from modifier methods.
	 */
	public static function process_modifiers( $value, $merge_tag, $modifier, $field, $raw_value ) {

		// Process array value for sub fields like name and address.
		if ( is_array( $raw_value ) && isset( $raw_value[ $merge_tag ] ) ) {
			$raw_value = $raw_value[ $merge_tag ];
		}

		// No modifier was set or the raw value was empty
		if ( 'all_fields' === $merge_tag || '' === $modifier || ! is_string( $raw_value ) || '' === $raw_value ) {
			return $value;
		}

		// Retrieve the original case-sensitive modifier.
		$modifier = self::$merge_tag_modifiers[ strtolower( $modifier ) ] ?? $modifier;

		// matching regex => the value is the method to call to replace the value.
		$gv_modifiers = array(
			'maxwords:(\d+)'            => 'modifier_maxwords', /** @see modifier_maxwords */
			'timestamp'                 => 'modifier_timestamp', /** @see modifier_timestamp */
			'explode'                   => 'modifier_explode', /** @see modifier_explode */
			'urlencode'                 => 'modifier_strings', /** @see modifier_strings */
			'wpautop'                   => 'modifier_strings',
			'esc_html'                  => 'modifier_strings',
			'sanitize_html_class'       => 'modifier_strings',
			'sanitize_title'            => 'modifier_strings',
			'strtolower'                => 'modifier_strings',
			'strtoupper'                => 'modifier_strings',
			'ucfirst'                   => 'modifier_strings',
			'ucwords'                   => 'modifier_strings',
			'wptexturize'               => 'modifier_strings',
			'initials'                  => 'modifier_initials', /** @see modifier_initials */
			'format'                    => 'modifier_format', /** @see modifier_format */
			'human'						=> 'modifier_human', /** @see modifier_human */
		);

		$modifiers = explode( ',', $modifier );

		$return = $raw_value;

		$unserialized = maybe_unserialize( $raw_value );

		if ( method_exists( $field, 'get_value_merge_tag' ) && is_array( $unserialized ) ) {

			$non_gv_modifiers = array_diff( $modifiers, array_keys( $gv_modifiers ) );

			$return = $field->get_value_merge_tag( $value, '', array( 'currency' => '' ), array(), implode( '', $non_gv_modifiers ), $raw_value, false, false, 'text', false );
		}

		foreach ( $modifiers as $passed_modifier ) {

			foreach ( $gv_modifiers as $gv_modifier => $method ) {

				// Uses ^ to only match the first modifier, to enforce same order as passed by GF
				preg_match( '/^' . $gv_modifier . '/ism', $passed_modifier, $matches );

				if ( empty( $matches ) ) {
					continue;
				}

				// The called method is passed the raw value and the full matches array
				$return = self::$method( $return, $matches, $value, $field, $passed_modifier );
				break;
			}
		}

		// No GravityView modifications were made; return the (default) original value
		if ( $raw_value === $return ) {
			return $value;
		}

		/**
		 * Modify the merge tag modifier output.
		 *
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
	 * Converts date and time values to the human format modifier.
	 *
	 * @since 2.29.0
	 *
	 * @param string $raw_value The raw value to modify.
	 * @param array  $matches   Array of regex matches.
	 * @param string $value     The original value.
	 * @param array  $field     The field object.
	 * @param string $modifier  The modifier string.
	 *
	 * @return string
	 */
	public static function modifier_human( $raw_value, $matches, $value = '', $field = null, $modifier = '' ) {
		// Check if the value is a valid date.
		$timestamp = strtotime( $raw_value );

		if ( false === $timestamp || ( ! $field instanceof GF_Field_Date && ! $field instanceof GF_Field_Time ) ) {
			return $raw_value;
		}

		$args = [
			'human' => true,
			'diff'  => true,
		];

		if ( $field instanceof GF_Field_Time ) {
			$args['time'] = true;
		}

		return GVCommon::format_date( $raw_value, $args );
	}

	/**
	 * Converts date and time values to the format modifier.
	 *
	 * @since 2.26
	 *
	 * @param string $raw_value
	 * @param array  $matches
	 * @param string $value
	 * @param array  $field
	 * @param string $modifier
	 *
	 * @return string
	 */
	private static function modifier_format( $raw_value, $matches, $value, $field, $modifier ) {
		$format = self::get_format_merge_tag_modifier_value( $modifier );

		if ( ! $format ) {
			return $raw_value;
		}

		if ( $field instanceof GF_Field_Time ) {
			return ( new DateTime( $raw_value ) )->format( $format ); // GF's Time field always uses local time.
		}

		if ( $field instanceof GF_Field_Date ) {
			return self::format_date( $raw_value, $modifier );
		}

		return $raw_value;
	}

	/**
	 * Convert Date field values to timestamp int
	 *
	 * @since 1.17
	 *
	 * @uses strtotime()
	 *
	 * @param string $raw_value Value to filter
	 * @param array  $matches Regex matches group
	 *
	 * @return int Timestamp value of date. `-1` if not a valid timestamp.
	 */
	private static function modifier_timestamp( $raw_value, $matches ) {

		if ( empty( $matches[0] ) ) {
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
	 * @param string         $raw_value Value to filter
	 * @param array          $matches Regex matches group
	 * @param GF_Field|false $field
	 *
	 * @return string Modified value, if longer than the passed `maxwords` modifier
	 */
	private static function modifier_maxwords( $raw_value, $matches, $field = null ) {

		if ( ! is_string( $raw_value ) || empty( $matches[1] ) || ! function_exists( 'wp_trim_words' ) ) {
			return $raw_value;
		}

		$max = intval( $matches[1] );

		$more_placeholder = '[GVMORE]';

		/**
		 * Use htmlentities instead, so that entities are double-encoded, and decoding restores original values.
		 *
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
	 * @param string         $value
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
	 * @param mixed          $raw_value The raw value submitted for this field. May be CSV or JSON-encoded.
	 * @param array          $matches Regex matches group
	 * @param string         $value The value as passed by Gravity Forms
	 * @param GF_Field|false $field Gravity Forms field, if any
	 *
	 * @return string
	 */
	private static function modifier_explode( $raw_value, $matches, $value, $field = null ) {

		// For JSON-encoded arrays
		if ( $json_array = json_decode( $raw_value, true ) ) {
			return implode( ' ', $json_array );
		}

		return implode( ' ', explode( ',', $raw_value ) );
	}

	/**
	 * Process strings with common PHP string manipulations
	 *
	 * @since 2.0
	 *
	 * @param mixed          $raw_value The raw value submitted for this field. May be CSV or JSON-encoded.
	 * @param array          $matches Regex matches group
	 * @param string         $value The value as passed by Gravity Forms
	 * @param GF_Field|false $field Gravity Forms field, if any
	 *
	 * @return string
	 */
	private static function modifier_strings( $raw_value, $matches, $value = '', $field = null ) {

		if ( empty( $matches[0] ) ) {
			return $raw_value;
		}

		$return = $raw_value;

		switch ( $matches[0] ) {
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
	 * Adds a modifier to convert a full name or string to initials.
	 *
	 * @since TBD
	 *
	 * @param string $raw_value The full name or string to convert.
	 * 
	 * @return string The initials.
	 */
	public static function modifier_initials( $raw_value ) {
		return GravityView_Field_Name::convert_to_initials( $raw_value );
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
	 * @param  string $text         Text to replace variables in.
	 * @param  array  $form         GF Form array
	 * @param  array  $entry        GF Entry array
	 * @param  bool   $url_encode   Pass return value through `url_encode()`
	 * @param  bool   $esc_html     Pass return value through `esc_html()`
	 * @param  bool   $nl2br        Convert newlines to <br> HTML tags
	 * @param  string $format       The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param  array  $aux_data     Additional data to be used to replace merge tags {@see https://www.gravityhelp.com/documentation/article/gform_merge_tag_data/}
	 * @return string           Text with variables maybe replaced
	 */
	public static function replace_variables( $text, $form = array(), $entry = array(), $url_encode = false, $esc_html = true, $nl2br = true, $format = 'html', $aux_data = array() ) {

		if ( ! is_string( $text ) ) {
			gravityview()->log->notice( '$text is not a string.', array( 'data' => $text ) );
			return $text;
		}

		/**
		 * Turn off merge tag variable replacements.\n.
		 * Useful where you want to process variables yourself. We do this in the Math Extension.
		 *
		 * @since 1.13
		 *
		 * @param boolean $do_replace_variables True: yes, replace variables for this text; False: do not replace variables.
		 * @param string $text       Text to replace variables in
		 * @param  array      $form        GF Form array
		 * @param  array      $entry        GF Entry array
		 */
		$do_replace_variables = apply_filters( 'gravityview/merge_tags/do_replace_variables', true, $text, $form, $entry );

		if ( false === strpos( $text, '{' ) || ! $do_replace_variables ) {
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
	 * @param string     $text Text to replace
	 * @param array|bool $form Gravity Forms form array. When called inside {@see GFCommon::replace_variables()} (now deprecated), `false`
	 * @param array|bool $entry Entry array.  When called inside {@see GFCommon::replace_variables()} (now deprecated), `false`
	 * @param bool       $url_encode Whether to URL-encode output
	 * @param bool       $esc_html Whether to apply `esc_html()` to output
	 *
	 * @return mixed
	 */
	public static function replace_gv_merge_tags( $text, $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		if ( '' === $text ) {
			return $text;
		}

		$text = self::replace_is_starred( $text, $form, $entry, $url_encode, $esc_html );

		$text = self::replace_site_url( $text, $form, $entry, $url_encode, $esc_html );

		$text = self::replace_get_variables( $text, $form, $entry, $url_encode );

		$text = self::replace_current_post( $text, $form, $entry, $url_encode, $esc_html );

		$text = self::replace_entry_link( $text, $form, $entry, $url_encode, $esc_html );

		$text = self::replace_merge_tags_dates( $text );

		return $text;
	}

	/**
	 * Replaces relative date merge tags with formatted dates per the modifier.
	 *
	 * @since 2.30.0
	 *
	 * @param string $text The text containing merge tags.
	 *
	 * @return string The text with date merge tags replaced.
	 */
	public static function replace_merge_tags_dates( $text ) {
		if ( false === strpos( $text, '{' ) ) {
			return $text;
		}

		preg_match_all( '/{(now|yesterday|tomorrow):?(.*?)(?:\s)?}/ism', $text, $matches, PREG_SET_ORDER );

		if ( empty( $matches ) ) {
			return $text;
		}

		$utc_timestamp   = time();
		$local_timestamp = GFCommon::get_local_timestamp( $utc_timestamp );

		foreach ( $matches as $match ) {
			$modifier = $match[2];

			if ( strpos( $modifier, 'timestamp' ) !== false ) {
				$local_timestamp = $utc_timestamp;
			}

			$replacements = [
				'now'       => date_i18n( 'Y-m-d H:i:s', $local_timestamp, true ),
				'yesterday' => date_i18n( 'Y-m-d H:i:s', $local_timestamp - DAY_IN_SECONDS, true ),
				'tomorrow'  => date_i18n( 'Y-m-d H:i:s', $local_timestamp + DAY_IN_SECONDS, true ),
			];

			$full_tag         = $match[0];
			$replaceable_date = $replacements[ $match[1] ];
			$formatted_date   = self::format_date( $replaceable_date, $modifier );
			$text             = str_replace( $full_tag, $formatted_date, $text );
		}

		return $text;
	}

	/**
	 * Add a {is_starred} Merge Tag
	 *
	 * @since 2.14
	 *
	 * @param string $original_text Text to replace
	 * @param array  $form Gravity Forms form array
	 * @param array  $entry Entry array
	 * @param bool   $url_encode Whether to URL-encode output
	 * @param bool   $esc_html Indicates if the esc_html function should be applied.
	 *
	 * @return string Original text, if no {site_url} Merge Tags found, otherwise text with Merge Tag replaced
	 */
	public static function replace_is_starred( $original_text, $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		if ( false === strpos( $original_text, '{is_starred}' ) ) {
			return $original_text;
		}

		return str_replace( '{is_starred}', rgar( $entry, 'is_starred', '' ), $original_text );
	}

	/**
	 * Add a {site_url} Merge Tag
	 *
	 * @since 2.10.1
	 *
	 * @param string $original_text Text to replace
	 * @param array  $form Gravity Forms form array
	 * @param array  $entry Entry array
	 * @param bool   $url_encode Whether to URL-encode output
	 * @param bool   $esc_html Indicates if the esc_html function should be applied.
	 *
	 * @return string Original text, if no {site_url} Merge Tags found, otherwise text with Merge Tag replaced
	 */
	public static function replace_site_url( $original_text, $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		if ( false === strpos( $original_text, '{site_url}' ) ) {
			return $original_text;
		}

		$site_url = get_site_url();

		if ( $url_encode ) {
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
	 * @param array  $form Gravity Forms form array
	 * @param array  $entry Entry array
	 * @param bool   $url_encode Whether to URL-encode output
	 * @param bool   $esc_html Indicates if the esc_html function should be applied.
	 *
	 * @return string Original text, if no {gv_entry_link} Merge Tags found, otherwise text with Merge Tags replaced
	 */
	public static function replace_entry_link( $original_text, $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		// Is there is {gv_entry_link} or {gv_entry_link:[post id]} or {gv_entry_link:[post id]:[action]} merge tag?
		preg_match_all( '/{gv_entry_link(?:\:(\d+)\:?(.*?))?}/ism', $original_text, $matches, PREG_SET_ORDER );

		if ( empty( $matches ) ) {
			return $original_text;
		}

		if ( ! class_exists( 'GravityView_Entry_Link_Shortcode' ) ) {
			gravityview()->log->error( 'GravityView_Entry_Link_Shortcode not found' );
			return $original_text;
		}

		$Shortcode = new GravityView_Entry_Link_Shortcode();

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
				'return'   => 'url',
				'entry_id' => $entry['id'],
				'post_id'  => \GV\Utils::get( $match, 1, null ),
				'action'   => \GV\Utils::get( $match, 2, 'read' ),
			);

			$entry_link = $Shortcode->read_shortcode( $link_args, null, 'gv_entry_link_merge_tag' );

			if ( $url_encode ) {
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
	 * Formats merge tag value using Merge Tags using GVCommon::format_date()
	 *
	 * @todo  This is no longer needed since Gravity Forms 2.5 as it supports modifiers, but should be reviewed before removal.
	 *
	 * @since 1.16
	 *
	 * @see   https://docs.gravitykit.com/article/331-date-created-merge-tag for documentation
	 * @uses  GVCommon::format_date()
	 *
	 * @param string $date_or_time_string The Gravity Forms date or time string.
	 * @param string $modifier            Merge tag modifier (`human`, `format:m/d/Y`)
	 *
	 * @return int|string If timestamp requested, timestamp int. Otherwise, string output.
	 */
	public static function format_date( $date_or_time_string = '', $modifier = '' ) {
		$parsed_modifier = explode( ':', $modifier );

		$atts = [
			'format'    => self::get_format_merge_tag_modifier_value( $modifier, false ),
			'human'     => in_array( 'human', $parsed_modifier ), // {date_created:human}
			'diff'      => in_array( 'diff', $parsed_modifier ), // {date_created:diff}
			'raw'       => in_array( 'raw', $parsed_modifier ), // {date_created:raw}
			'timestamp' => in_array( 'timestamp', $parsed_modifier ), // {date_created:timestamp}
			'time'      => in_array( 'time', $parsed_modifier ),  // {date_created:time}
		];

		return GVCommon::format_date( $date_or_time_string, $atts );
	}

	/**
	 * Returns the `format:` merge tag modifier value.
	 * This handles cases such as "foo:format:m/d/Y", "format:m/d/Y", "format:m/d/Y\ \a\t\ H\:i\:s".
	 *
	 * @since 1.16
	 * @since 2.27 Renamed and refactored to use regex and instead of working with an array.
	 *
	 * @param string $modifier Merge tag modifier.
	 * @param mixed  $backup   The backup value to use, if format not found.
	 *
	 * @return string If format is found, the passed format. Otherwise, the backup.
	 */
	private static function get_format_merge_tag_modifier_value( $modifier, $backup = '' ) {
		preg_match( '/(?:^|:)format:(.*)/', $modifier, $match );

		return isset( $match[1] ) ? str_replace( '\:', ':', $match[1] ) : $backup;
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
	 * @param array  $form Gravity Forms form array
	 * @param array  $entry Entry array
	 * @param bool   $url_encode Whether to URL-encode output
	 * @param bool   $esc_html Indicates if the esc_html function should be applied.
	 *
	 * @return string Original text, if no {current_post} Merge Tags found, otherwise text with Merge Tags replaced
	 */
	public static function replace_current_post( $original_text, $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		$return = $original_text;

		// Is there a {current_post} or {current_post:[xyz]} merge tag?
		preg_match_all( '/{current_post(:(.*?))?}/ism', $original_text, $matches, PREG_SET_ORDER );

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

				if ( $url_encode ) {
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
	 *     [else]
	 *          ?example wasn't "false". It's {get:example}!
	 *     [/gvlogic]
	 *
	 * Supports passing arrays:
	 *     URL: `example[]=Example+One&example[]=Example+(with+comma)%2C+Two`
	 *     Merge Tag: `{get:example}`
	 *     Output: `Example One, Example (with comma), Two`
	 *
	 * @since 1.15
	 * @param string $text Text to replace
	 * @param array  $form Gravity Forms form array
	 * @param array  $entry Entry array
	 * @param bool   $url_encode Whether to URL-encode output
	 *
	 * @return string Original text, if no Merge Tags found, otherwise text with Merge Tags replaced
	 */
	public static function replace_get_variables( $text, $form = array(), $entry = array(), $url_encode = false ) {

		// Is there is {get:[xyz]} merge tag?
		preg_match_all( '/{get:(.*?)}/ism', $text, $matches, PREG_SET_ORDER );

		// If there are no matches OR the Entry `created_by` isn't set or is 0 (no user)
		if ( empty( $matches ) ) {
			return $text;
		}

		foreach ( $matches as $match ) {

			$full_tag = $match[0];
			$property = $match[1];

			$value = stripslashes_deep( \GV\Utils::_GET( $property ) );

			/**
			 * values from an array to string.
			 *
			 * @since 1.15
			 * @param string $glue String used to `implode()` $_GET values Default: ', '
			 * @param string $property The current name of the $_GET parameter being combined
			 */
			$glue = apply_filters( 'gravityview/merge_tags/get/glue/', ', ', $property );

			$value = is_array( $value ) ? implode( $glue, $value ) : $value;

			$value = $url_encode ? urlencode( $value ) : $value;

			/**
			 * merge tag.
			 * By default, all values passed through URLs will be escaped for security reasons. If for some reason you want to
			 * pass HTML in the URL, for example, you will need to return false on this filter. It is strongly recommended that you do
			 * not disable this filter.
			 *
			 * @since 1.15
			 * @param bool $esc_html Whether to esc_html() the value. Default: `true`
			 */
			$esc_html = apply_filters( 'gravityview/merge_tags/get/esc_html/' . $property, true );

			$value = $esc_html ? esc_html( $value ) : $value;

			/**
			 * replacement before being used.
			 *
			 * @param string $value Value that will replace `{get}`
			 * @param string $text Text that contains `{get}` (before replacement)
			 * @param array $form Gravity Forms form array
			 * @param array $entry Entry array
			 */
			$value = apply_filters( 'gravityview/merge_tags/get/value/' . $property, $value, $text, $form, $entry );

			$text = str_replace( $full_tag, $value, $text );
		}

		unset( $value, $glue, $matches );

		return $text;
	}
}

new GravityView_Merge_Tags();
