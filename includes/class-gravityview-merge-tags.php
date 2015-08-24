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

		add_filter( 'gform_replace_merge_tags', array( $this, '_gform_replace_merge_tags' ), 10, 7 );

	}

	/**
	 * Check for merge tags before passing to Gravity Forms to improve speed.
	 *
	 * GF doesn't check for whether `{` exists before it starts diving in. They not only replace fields, they do `str_replace()` on things like ip address, which is a lot of work just to check if there's any hint of a replacement variable.
	 *
	 * We check for the basics first, which is more efficient.
	 *
	 * @since 1.8.4 - Moved to GravityView_Merge_Tags
	 *
	 * @param  string      $text       Text to replace variables in
	 * @param  array      $form        GF Form array
	 * @param  array      $entry        GF Entry array
	 * @return string                  Text with variables maybe replaced
	 */
	public static function replace_variables( $text, $form, $entry ) {

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

		// Check for fields - if they exist, we let Gravity Forms handle it.
		preg_match_all('/{[^{]*?:(\d+(\.\d+)?)(:(.*?))?}/mi', $text, $matches, PREG_SET_ORDER);

		if( empty( $matches ) ) {

			// Check for form variables
			if( !preg_match( '/\{(all_fields(:(.*?))?|pricing_fields|form_title|entry_url|ip|post_id|admin_email|post_edit_url|form_id|entry_id|embed_url|date_mdy|date_dmy|embed_post:(.*?)|custom_field:(.*?)|user_agent|referer|gv:(.*?)|user:(.*?)|created_by:(.*?))\}/ism', $text ) ) {
				return $text;
			}
		}

		return GFCommon::replace_variables( $text, $form, $entry, false, false, false, "html");
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
			)
		);

		//return the form object from the php hook
		return array_merge( $existing_merge_tags, $created_by_merge_tags );
	}

	/**
	 * Instead of adding multiple hooks, add all hooks into this one method to improve speed
	 *
	 * @since 1.8.4
	 *
	 * @param string $text Text to replace
	 * @param array|boolean $form Gravity Forms form array
	 * @param array $entry Entry array
	 * @param bool $url_encode Whether to URL-encode output
	 * @param bool $esc_html Whether to apply `esc_html()` to output
	 *
	 * @return mixed
	 */
	public function _gform_replace_merge_tags(  $text, $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		/**
		 * This prevents the gform_replace_merge_tags filter from being called twice, as defined in:
		 * @see GFCommon::replace_variables()
		 * @see GFCommon::replace_variables_prepopulate()
		 */
		if( false === $form ) {
			return $text;
		}

		// Process the merge vars here
		$text = $this->replace_user_variables_created_by( $text, $form, $entry, $url_encode, $esc_html );

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
	private function replace_user_variables_created_by( $text, $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

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

			$value = $entry_creator->get( $property );

			$value = $url_encode ? urlencode( $value ) : $value;

			$esc_html = $esc_html ? esc_html( $value ) : $value;

			$text = str_replace( $full_tag, $value, $text );
		}

		return $text;
	}
}

new GravityView_Merge_Tags;