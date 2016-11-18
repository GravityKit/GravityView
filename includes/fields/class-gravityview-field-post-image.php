<?php
/**
 * @file class-gravityview-field-post-image.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Add custom options for Post Image fields
 */
class GravityView_Field_Post_Image extends GravityView_Field {

	var $name = 'post_image';

	var $_gf_field_class_name = 'GF_Field_Post_Image';

	var $group = 'post';

	public function __construct() {
		$this->label = esc_html__( 'Post Image', 'gravityview' );
		parent::__construct();
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		unset ( $field_options['search_filter'] );

		if( 'edit' === $context ) {
			return $field_options;
		}

		$this->add_field_support('link_to_post', $field_options );

		// @since 1.5.4
		$this->add_field_support('dynamic_data', $field_options );

		return $field_options;
	}

	/**
	 * Convert Gravity Forms `|:|`-separated image data into an array
	 *
	 * If passed something other than a string, returns the passed value.
	 *
	 * @since 1.16.2
	 * @since 1.19.2 Converted from private to static public method
	 *
	 * @param string $value The stored value of an image, impoded with `|:|` values
	 *
	 * @return array with `url`, `title`, `caption` and `description` values
	 */
	static public function explode_value( $value ) {

		// Already is an array, perhaps?
		if ( ! is_string( $value ) ) {
			return $value;
		}

		$url = $title = $caption = $description = '';

		// If there's a |:| match, process. Otherwise, empty array!
		if( preg_match( '/\|\:\|/', $value ) ) {
			list( $url, $title, $caption, $description ) = array_pad( explode( '|:|', $value ), 4, false );
		}

		return array(
			'url' => $url,
			'title' => $title,
			'caption' => $caption,
			'description' => $description,
		);
	}

	/**
	 * Returns the field inner markup
	 *
	 * Overriding GF_Field_Post_Image is necessary because they don't check for existing post image values, because
	 * GF only creates, not updates.
	 *
	 * @since 1.16.2
	 *
	 * @param array $form The Form Object currently being processed.
	 * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array $entry Null or the Entry Object currently being edited.
	 * @param GF_Field_Post_Image $field
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null, GF_Field_Post_Image $field ) {

		$id = (int) $field->id;
		$form_id = $form['id'];
		$input_name = "input_{$id}";
		$field_id = sprintf( 'input_%d_%d', $form_id, $id );
		$img_name = null;

		// Convert |:| to associative array
		$img_array = self::explode_value( $value );

		if( ! empty( $img_array['url'] ) ) {

			$img_name = basename( $img_array['url'] );

			/**
			 * Set the $uploaded_files value so that the .ginput_preview renders, and the file upload is hidden
			 * @see GF_Field_Post_Image::get_field_input See the `<span class='ginput_preview'>` code
			 * @see GFFormsModel::get_temp_filename See the `rgget( $input_name, self::$uploaded_files[ $form_id ] );` code
			 */
			if( empty( GFFormsModel::$uploaded_files[ $form_id ][ $input_name ] ) ) {
				GFFormsModel::$uploaded_files[ $form_id ][ $input_name ] = $img_name;
			}
		}

		// Tell Gravity Forms we're not in the Admin
		add_filter( 'gform_is_entry_detail', '__return_false' );
		add_filter( 'gform_is_form_editor', '__return_false' );

		$input_value = array(
			"{$id}.1" => rgar( $img_array, 'title' ),
			"{$id}.4" => rgar( $img_array, 'caption' ),
			"{$id}.7" => rgar( $img_array, 'description' ),
		);

		// Get the field HTML output
		$gf_post_image_field_output = $field->get_field_input( $form, $input_value );

		// Clean up our own filters
		remove_filter( 'gform_is_entry_detail', '__return_false' );
		remove_filter( 'gform_is_form_editor', '__return_false' );

		/**
		 * Insert a hidden field into the output that is used to store the image URL
		 * @var string $current_file We need to have a reference of whether same file is being updated, or user wants to remove the image.
		 * @see \GravityView_Edit_Entry_Render::maybe_update_post_fields
		 * @hack
		 */
		if ( null !== $img_name ) {
			$current_file = sprintf( "<input name='%s' id='%s' type='hidden' value='%s' />", $input_name, $field_id, esc_url_raw( $img_array['url'] ) );
			$gf_post_image_field_output = str_replace('<span class=\'ginput_preview\'>', '<span class=\'ginput_preview\'>'.$current_file, $gf_post_image_field_output );
		}

		return $gf_post_image_field_output;
	}

}

new GravityView_Field_Post_Image;
