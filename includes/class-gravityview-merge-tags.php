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
	 *
	 * @param  string      $text       Text to replace variables in
	 * @param  array      $form        GF Form array
	 * @param  array      $entry        GF Entry array
	 * @param  bool       $url_encode   Pass return value through `url_encode()`
	 * @param  bool       $esc_html     Pass return value through `esc_html()`
	 * @return string                  Text with variables maybe replaced
	 */
	public static function replace_variables($text, $form = array(), $entry = array(), $url_encode = false, $esc_html = true ) {

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
		 * @internal Reported to GF Support on 12/3
		 * @internal Fixed in Gravity Forms
		 */
		$form['title']  = isset( $form['title'] ) ? $form['title'] : '';
		$form['id']     = isset( $form['id'] ) ? $form['id'] : '';
		$form['fields'] = isset( $form['fields'] ) ? $form['fields'] : array();

		return GFCommon::replace_variables( $text, $form, $entry, $url_encode, $esc_html );
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
	public static function replace_gv_merge_tags(  $text, $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		/**
		 * This prevents the gform_replace_merge_tags filter from being called twice, as defined in:
		 * @see GFCommon::replace_variables()
		 * @see GFCommon::replace_variables_prepopulate()
		 * @todo Remove eventually: Gravity Forms fixed this issue in 1.9.14
		 */
		if( false === $form ) {
			return $text;
		}

		$text = self::replace_get_variables( $text, $form, $entry, $url_encode );

		return $text;
	}

	/**
	 * Format Merge Tags using GVCommon::format_date()
	 *
	 * @uses GVCommon::format_date()
	 *
	 * @see http://docs.gravityview.co/article/331-date-created-merge-tag for documentation
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

			$value = stripslashes_deep( rgget( $property ) );

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
