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

		add_filter( 'gform_custom_merge_tags', array( $this, '_gform_custom_merge_tags' ), 10, 4 );

		/** @see GFCommon::replace_variables_prepopulate **/
		add_filter( 'gform_replace_merge_tags', array( 'GravityView_Merge_Tags', 'replace_gv_merge_tags' ), 10, 7 );

	}

	/**
	 * Check for merge tags before passing to Gravity Forms to improve speed.
	 *
	 * GF doesn't check for whether `{` exists before it starts diving in. They not only replace fields, they do `str_replace()` on things like ip address, which is a lot of work just to check if there's any hint of a replacement variable.
	 *
	 * We check for the basics first, which is more efficient.
	 *
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
		 * @param[in,out] boolean $do_replace_variables True: yes, replace variables for this text; False: do not replace variables.
		 * @param[in] string $text       Text to replace variables in
		 * @param[in]  array      $form        GF Form array
		 * @param[in]  array      $entry        GF Entry array
		 */
		$do_replace_variables = apply_filters( 'gravityview/merge_tags/do_replace_variables', true, $text, $form, $entry );

		if( strpos( $text, '{') === false || ! $do_replace_variables ) {
			return $text;
		}

		/**
		 * Replace GravityView merge tags before going to Gravity Forms
		 * This allows us to replace our tags first.
		 * @since 1.15
		 */
		$text = self::replace_gv_merge_tags( $text, $form, $entry );

		// Check for fields - if they exist, we let Gravity Forms handle it.
		preg_match_all('/{[^{]*?:(\d+(\.\d+)?)(:(.*?))?}/mi', $text, $matches, PREG_SET_ORDER);

		if( empty( $matches ) ) {

			// Check for form variables
			if( !preg_match( '/\{(all_fields(:(.*?))?|all_fields_display_empty|pricing_fields|form_title|entry_url|ip|post_id|admin_email|post_edit_url|form_id|entry_id|embed_url|date_mdy|date_dmy|embed_post:(.*?)|custom_field:(.*?)|user_agent|referer|gv:(.*?)|get:(.*?)|user:(.*?)|created_by:(.*?))\}/ism', $text ) ) {
				return $text;
			}
		}

		return GFCommon::replace_variables( $text, $form, $entry, $url_encode, $esc_html );
	}

	/**
	 * Add custom merge tags to merge tag options
	 *
	 * @since 1.8.4
	 *
	 * @param array $existing_merge_tags
	 * @param int $form_id GF Form ID
	 * @param GF_Field[] $fields Array of fields in the form
	 * @param string $element_id The ID of the input that Merge Tags are being used on
	 *
	 * @return array Modified merge tags
	 */
	public function _gform_custom_merge_tags( $existing_merge_tags = array(), $form_id, $fields = array(), $element_id = '' ) {

		$created_by_merge_tags = array(
			array(
				'label' => __('Entry Creator: Display Name', 'gravityview'),
				'tag' => '{created_by:display_name}'
			),
			array(
				'label' => __('Entry Creator: Email', 'gravityview'),
				'tag' => '{created_by:user_email}'
			),
			array(
				'label' => __('Entry Creator: Username', 'gravityview'),
				'tag' => '{created_by:user_login}'
			),
			array(
				'label' => __('Entry Creator: User ID', 'gravityview'),
				'tag' => '{created_by:ID}'
			),
			array(
				'label' => __('Entry Creator: Roles', 'gravityview'),
				'tag' => '{created_by:roles}'
			),
		);

		//return the form object from the php hook
		return array_merge( $existing_merge_tags, $created_by_merge_tags );
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
		 */
		if( false === $form ) {
			return $text;
		}

		$text = self::replace_get_variables( $text, $form, $entry, $url_encode );

		// Process the merge vars here
		$text = self::replace_user_variables_created_by( $text, $form, $entry, $url_encode, $esc_html );

		return $text;
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

	/**
	 * Exactly like Gravity Forms' User Meta functionality, but instead shows information on the user who created the entry
	 * instead of the currently logged-in user.
	 *
	 * @see http://docs.gravityview.co/article/281-the-createdby-merge-tag Read how to use the `{created_by}` merge tag
	 *
	 * @since 1.8.4
	 *
	 * @param string $text Text to replace
	 * @param array $form Gravity Forms form array
	 * @param array $entry Entry array
	 * @param bool $url_encode Whether to URL-encode output
	 * @param bool $esc_html Whether to apply `esc_html()` to output
	 *
	 * @return string Text, with user variables replaced, if they existed
	 */
	private static function replace_user_variables_created_by( $text, $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		// Is there is {created_by:[xyz]} merge tag?
		preg_match_all( "/\{created_by:(.*?)\}/", $text, $matches, PREG_SET_ORDER );

		// If there are no matches OR the Entry `created_by` isn't set or is 0 (no user)
		if( empty( $matches ) || empty( $entry['created_by'] ) ) {
			return $text;
		}

		// Get the creator of the entry
		$entry_creator = new WP_User( $entry['created_by'] );

		foreach ( $matches as $match ) {

			$full_tag = $match[0];
			$property = $match[1];

			switch( $property ) {
				/** @since 1.13.2 */
				case 'roles':
					$value = implode( ', ', $entry_creator->roles );
					break;
				default:
					$value = $entry_creator->get( $property );
			}

			$value = $url_encode ? urlencode( $value ) : $value;

			$value = $esc_html ? esc_html( $value ) : $value;

			$text = str_replace( $full_tag, $value, $text );
		}

		unset( $entry_creator );

		return $text;
	}
}

new GravityView_Merge_Tags;
