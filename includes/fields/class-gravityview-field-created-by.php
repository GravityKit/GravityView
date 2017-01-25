<?php
/**
 * @file class-gravityview-field-created-by.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Created_By extends GravityView_Field {

	var $name = 'created_by';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot' );

	var $group = 'meta';

	var $_custom_merge_tag = 'created_by';

	public function __construct() {
		$this->label = esc_html__( 'Created By', 'gravityview' );
		$this->default_search_label = __( 'Submitted by:', 'gravityview' );
		parent::__construct();
	}

	/**
	 * Add custom merge tags to merge tag options
	 *
	 * @since 1.16
	 *
	 * @param array $form GF Form array
	 * @param GF_Field[] $fields Array of fields in the form
	 *
	 * @return array Modified merge tags
	 */
	protected function custom_merge_tags( $form = array(), $fields = array() ) {

		$merge_tags = array(
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

		return $merge_tags;
	}

	/**
	 * Exactly like Gravity Forms' User Meta functionality, but instead shows information on the user who created the entry
	 * instead of the currently logged-in user.
	 *
	 * @see http://docs.gravityview.co/article/281-the-createdby-merge-tag Read how to use the `{created_by}` merge tag
	 *
	 * @since 1.16
	 *
	 * @param array $matches Array of Merge Tag matches found in text by preg_match_all
	 * @param string $text Text to replace
	 * @param array $form Gravity Forms form array
	 * @param array $entry Entry array
	 * @param bool $url_encode Whether to URL-encode output
	 * @param bool $esc_html Whether to apply `esc_html()` to output
	 *
	 * @return string Text, with user variables replaced, if they existed
	 */
	public function replace_merge_tag( $matches = array(), $text = '', $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		// If there are no matches OR the Entry `created_by` isn't set or is 0 (no user)
		if( empty( $entry['created_by'] ) ) {
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

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		if( 'edit' === $context ) {
			return $field_options;
		}

		$field_options['name_display'] = array(
			'type' => 'select',
			'label' => __( 'User Format', 'gravityview' ),
			'desc' => __( 'How should the User information be displayed?', 'gravityview'),
			'choices' => array(
				'display_name' => __('Display Name (Example: "Ellen Ripley")', 'gravityview'),
				'user_login' => __('Username (Example: "nostromo")', 'gravityview'),
				'ID' => __('User ID # (Example: 426)', 'gravityview'),
			),
			'value' => 'display_name'
		);

		return $field_options;
	}

}

new GravityView_Field_Created_By;
