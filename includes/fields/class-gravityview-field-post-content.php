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

	public function __construct() {
		$this->label = esc_html__( 'Post Content', 'gravityview' );
		parent::__construct();
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		unset( $field_options['show_as_link'] );

		if( 'edit' === $context ) {
			return $field_options;
		}

		$this->add_field_support('dynamic_data', $field_options );

		return $field_options;
	}

	/**
	 * Returns the field inner markup.
	 *
	 * @param array $form The Form Object currently being processed.
	 * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array $entry Null or the Entry Object currently being edited.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null, GF_Field_Post_Content $field ) {

		$id    = (int) $field->id;
		$input_name = "input_{$id}";
		$class = esc_attr( $field->size );
		$tabindex = $field->get_tabindex();

		$editor_settings = array(
			'editor_class'  => "textarea {$class}",
			'textarea_name' => $input_name,
			'textarea_rows' => 15,
			'tabindex'      => $tabindex,
			'media_buttons' => false,
			'quicktags' => false,
			'logic_event' => $field->get_conditional_logic_event( 'keyup' ),
			'placeholder' => $field->get_field_placeholder_attribute(),
		);

		/**
		 * @filter `gravityview/edit_entry/post_content/wp_editor_settings` Modify the settings passed to the Post Content wp_editor()
		 * @see wp_editor() For the options available
		 * @since 1.7
		 * @param array $editor_settings Array of settings to be passed to wp_editor(). Note: there are also two additional values in the array: `logic_event` and `placehodler`, added to the textarea HTML by GravityView.
		 */
		$editor_settings = apply_filters( 'gravityview/edit_entry/post_content/wp_editor_settings', $editor_settings );

		ob_start();
		wp_editor( $value, $input_name, $editor_settings );
		$editor = ob_get_clean();

		$logic_event = rgar( $editor_settings, 'logic_event' );
		$placeholder = rgar( $editor_settings, 'placeholder' );

		/** @internal Instead of using `add_filter('the_editor')` and doing the same thing, it's cleaner here. */
		$editor = str_replace( '<textarea ', "<textarea {$logic_event} {$placeholder}", $editor );

		return sprintf( "<div class='ginput_container ginput_container_post_content'>%s</div>", trim( $editor ) );
	}

}

new GravityView_Field_Post_Content;
