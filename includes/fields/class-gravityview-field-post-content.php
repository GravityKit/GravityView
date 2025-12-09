<?php
/**
 * @file class-gravityview-field-post-content.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Post_Content extends GravityView_Field {

	var $name = 'post_content';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot', 'contains', 'starts_with', 'ends_with' );

	var $_gf_field_class_name = 'GF_Field_Post_Content';

	var $group = 'post';

	var $icon = 'dashicons-editor-alignleft';

	public function __construct() {
		$this->label = esc_html__( 'Post Content', 'gk-gravityview' );
		parent::__construct();
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		unset( $field_options['show_as_link'] );

		if ( 'edit' === $context ) {
			return $field_options;
		}

		$this->add_field_support( 'dynamic_data', $field_options );

		return $field_options;
	}

	/**
	 * Returns the field inner markup.
	 *
	 * @param array                                             $form The Form Object currently being processed.
	 * @param string|array                                      $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array                                        $entry Null or the Entry Object currently being edited.
	 * @param null|GF_Field_Post_Content The field being edited.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null, GF_Field_Post_Content $field = null ) {

		$id         = (int) $field->id;
		$input_name = "input_{$id}";
		$class      = esc_attr( $field->size );
		$tabindex   = $field->get_tabindex();

		$editor_settings = array(
			'editor_class'  => "textarea {$class}",
			'textarea_name' => $input_name,
			'textarea_rows' => 15,
			'tabindex'      => $tabindex,
			'media_buttons' => false,
			'quicktags'     => false,
			'placeholder'   => $field->get_field_placeholder_attribute(),
		);

		/**
		 * Modify the settings passed to the Post Content wp_editor().
		 *
		 * @see wp_editor() For the options available
		 * @since 1.7
		 * @param array $editor_settings Array of settings to be passed to wp_editor(). Note: there is also an additional value in the array: `placeholder`, added to the textarea HTML by GravityView.
		 */
		$editor_settings = apply_filters( 'gravityview/edit_entry/post_content/wp_editor_settings', $editor_settings );

		ob_start();
		wp_editor( $value, $input_name, $editor_settings );
		$editor = ob_get_clean();

		$placeholder = \GV\Utils::get( $editor_settings, 'placeholder' );

		/** @internal Instead of using `add_filter('the_editor')` and doing the same thing, it's cleaner here. */
		$editor = str_replace( '<textarea ', "<textarea {$placeholder}", $editor );

		return sprintf( "<div class='ginput_container ginput_container_post_content'>%s</div>", trim( $editor ) );
	}
}

new GravityView_Field_Post_Content();
