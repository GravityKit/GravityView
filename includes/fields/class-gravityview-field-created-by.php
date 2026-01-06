<?php
/**
 * @file class-gravityview-field-created-by.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Created_By extends GravityView_Field {

	var $name = 'created_by';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot', 'in', 'not_in' );

	var $group = 'meta';

	var $_custom_merge_tag = 'created_by';

	var $icon = 'dashicons-admin-users';

	public function __construct() {
		$this->label                = esc_html__( 'Created By (User)', 'gk-gravityview' );
		$this->description          = __( 'Details of the logged-in user who created the entry (if any).', 'gk-gravityview' );
		$this->default_search_label = __( 'Submitted by:', 'gk-gravityview' );
		parent::__construct();
	}

	/**
	 * Add custom merge tags to merge tag options
	 *
	 * @since 1.16
	 *
	 * @param array      $form GF Form array
	 * @param GF_Field[] $fields Array of fields in the form
	 *
	 * @return array Modified merge tags
	 */
	protected function custom_merge_tags( $form = array(), $fields = array() ) {

		$merge_tags = array(
			array(
				'label' => __( 'Entry Creator: Display Name', 'gk-gravityview' ),
				'tag'   => '{created_by:display_name}',
			),
			array(
				'label' => __( 'Entry Creator: Email', 'gk-gravityview' ),
				'tag'   => '{created_by:user_email}',
			),
			array(
				'label' => __( 'Entry Creator: Username', 'gk-gravityview' ),
				'tag'   => '{created_by:user_login}',
			),
			array(
				'label' => __( 'Entry Creator: User ID', 'gk-gravityview' ),
				'tag'   => '{created_by:ID}',
			),
			array(
				'label' => __( 'Entry Creator: Roles', 'gk-gravityview' ),
				'tag'   => '{created_by:roles}',
			),
		);

		return $merge_tags;
	}

	/**
	 * Exactly like Gravity Forms' User Meta functionality, but instead shows information on the user who created the entry
	 * instead of the currently logged-in user.
	 *
	 * @see https://docs.gravitykit.com/article/281-the-createdby-merge-tag Read how to use the `{created_by}` merge tag
	 *
	 * @since 1.16
	 *
	 * @param array  $matches Array of Merge Tag matches found in text by preg_match_all
	 * @param string $text Text to replace
	 * @param array  $form Gravity Forms form array
	 * @param array  $entry Entry array
	 * @param bool   $url_encode Whether to URL-encode output
	 * @param bool   $esc_html Whether to apply `esc_html()` to output
	 *
	 * @return string Text, with user variables replaced, if they existed
	 */
	public function replace_merge_tag( $matches = array(), $text = '', $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		// If there are no matches OR the Entry `created_by` isn't set or is 0 (no user)
		if ( empty( $matches ) || empty( $entry['created_by'] ) ) {
			return $text;
		}

		// Get the creator of the entry
		$entry_creator = new WP_User( $entry['created_by'] );

		foreach ( $matches as $match ) {

			$full_tag = $match[0];
			$property = $match[1];

			switch ( $property ) {
				case '':
					$value = $entry_creator->ID;
					break;
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

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		if ( 'edit' === $context ) {
			return $field_options;
		}

		$field_options['name_display'] = array(
			'type'    => 'select',
			'label'   => __( 'User Format', 'gk-gravityview' ),
			'desc'    => __( 'How should the User information be displayed?', 'gk-gravityview' ),
			'choices' => array(
				// column
				'ID'              => __( 'User ID # (Example: 426)', 'gk-gravityview' ),
				'user_login'      => __( 'Username (Example: "nostromo")', 'gk-gravityview' ),
				'display_name'    => __( 'Display Name (Example: "Ellen Ripley")', 'gk-gravityview' ),
				'user_email'      => __( 'User Email (Example: "ellen@gravitykit.com")', 'gk-gravityview' ),
				'user_registered' => __( 'User Registered (Example: "2019-10-18 08:30:11")', 'gk-gravityview' ),

				// meta
				'nickname'        => ucwords( __( 'User nickname', 'gk-gravityview' ) ),
				'description'     => __( 'Description', 'gk-gravityview' ),
				'first_name'      => __( 'First Name', 'gk-gravityview' ),
				'last_name'       => __( 'Last Name', 'gk-gravityview' ),

				// misc
				'first_last_name' => __( 'First and Last Name', 'gk-gravityview' ),
				'last_first_name' => __( 'Last and First Name', 'gk-gravityview' ),
			),
			'value'   => 'display_name',
		);

		return $field_options;
	}

	/**
	 * Returns the HTML for the field input.
	 *
	 * @since 2.30.0
	 *
	 * @param array    $form  The form object.
	 * @param mixed    $value The field value.
	 * @param array    $entry The entry object.
	 * @param GF_Field $field The field object.
	 *
	 * @return string The input HTML.
	 */
	public function get_field_input( array $form, $value, array $entry, GF_Field $field ): string {
		GravityView_Change_Entry_Creator::enqueue_selectwoo_assets_frontend();
		wp_add_inline_style( 'gravityview_selectwoo', '
		.ginput_container .select2-container .select2-selection--single,
		.ginput_container .select2-container--default .select2-selection--single .select2-selection__arrow {
			height: 40px;
		}
		.ginput_container .select2-container--default .select2-selection--single .select2-selection__rendered {
			line-height: 40px;
		}
		');

		return sprintf(
			'<div class="ginput_container">%s</div>',
			GravityView_Change_Entry_Creator::get_select_field( $entry )
		);
	}
}

new GravityView_Field_Created_By();
