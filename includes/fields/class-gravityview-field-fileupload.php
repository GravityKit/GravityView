<?php
/**
 * @file class-gravityview-field-fileupload.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_FileUpload extends GravityView_Field {

	var $name = 'fileupload';

	var $_gf_field_class_name = 'GF_Field_FileUpload';

	var $is_searchable = true;

	var $search_operators = array( 'contains' );

	var $group = 'advanced';

	public function __construct() {
		$this->label = esc_html__( 'File Upload', 'gravityview' );
		parent::__construct();
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		unset( $field_options['search_filter'] );

		if( 'edit' === $context ) {
			return $field_options;
		}

		$add_options['link_to_file'] = array(
			'type' => 'checkbox',
			'label' => __( 'Display as a Link:', 'gravityview' ),
			'desc' => __('Display the uploaded files as links, rather than embedded content.', 'gravityview'),
			'value' => false,
			'merge_tags' => false,
		);

		return $add_options + $field_options;
	}

	/**
	 * Trick the GF fileupload field to render with the proper HTML ID to enable the plupload JS to work properly
	 *
	 * @param array               $form  The Form Object currently being processed.
	 * @param string|array        $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array          $entry Null or the Entry Object currently being edited.
	 * @param GF_Field_FileUpload $field Gravity Forms field
	 *
	 * @return string
	 */
	function get_field_input( $form, $field_value, $entry, $field ) {

		$field->_is_entry_detail = true;

		$return = $field->get_field_input( $form, $field_value, $entry );

		return $return;
	}

	/**
	 * Return an array of files prepared for output.
	 *
	 * Processes files by file type and generates unique output for each. Returns array for each file, with the following keys:
	 * - `file_path` => The file path of the file, with a line break
	 * - `html` => The file output HTML formatted
	 *
	 * @since  1.2
	 * @todo  Support `playlist` shortcode for playlist of video/audio
	 * @param  string $value    Field value passed by Gravity Forms. String of file URL, or serialized string of file URL array
	 * @param  string $gv_class Field class to add to the output HTML
	 *
	 * @since 2.0
	 * @param \GV\Template_Context The context.
	 *
	 * @return array           Array of file output, with `file_path` and `html` keys (see comments above)
	 */
	static function get_files_array( $value, $gv_class, $context = null ) {

		if ( $context instanceof \GV\Template_Context ) {
			$field = $context->field->field;
			$field_settings = $context->field->as_configuration();
			$entry = $context->entry->as_entry();
			$field_value = $context->value;
			global $post;
			$base_id = $post ? $post->ID : $context->view->ID;

			$is_single = $context->request->is_entry();
			$lightbox = $context->view->settings->get( 'lightbox', false );

			/** A compatibility array that's required by some of the deprecated filters. */
			$field_compat = array(
				'form' => $context->source->form,
				'field_id' => $context->field->ID,
				'field' => $field,
				'field_settings' => $field_settings,
				'value' => $field_value,
				'display_value' => $context->display_value,
				'format' => 'html',
				'entry' => $entry,
				'field_type' => $context->field->type,
				'field_path' => $context->template->located_template,
			);
		} else {
			$gravityview_view = GravityView_View::getInstance();
			/** @deprecated path */
			$gv_field_array = $gravityview_view->getCurrentField();

			/** @var GF_Field_FileUpload $field */
			$field = \GV\Utils::get( $gv_field_array, 'field' );
			$field_settings = \GV\Utils::get( $gv_field_array, 'field_settings' );
			$entry = \GV\Utils::get( $gv_field_array, 'entry' );
			$field_value = \GV\Utils::get( $gv_field_array, 'value' );
			$base_id = null;

			$is_single = gravityview_get_context() === 'single';
			$lightbox = ! empty( $gravityview_view->atts['lightbox'] );
			$field_compat = $gravityview_view->getCurrentField();
		}

		$output_arr = array();

		// Get an array of file paths for the field.
		$file_paths = \GV\Utils::get( $field , 'multipleFiles' ) ? json_decode( $value ) : array( $value );

		// The $value JSON was probably truncated; let's check lead_detail_long.
		if ( ! is_array( $file_paths ) ) {
			$full_value = RGFormsModel::get_lead_field_value( $entry, $field );
			$file_paths = json_decode( $full_value );
		}

		if ( ! is_array( $file_paths ) ) {
			gravityview()->log->error( 'Field does not have a valid image array. JSON decode may have failed.', array( 'data' => array( '$value' => $value, '$field_value' => $field_value ) ) );
			return $output_arr;
		}

		// Process each file path
		foreach( $file_paths as $file_path ) {

			// If the site is HTTPS, use HTTPS
			if(function_exists('set_url_scheme')) { $file_path = set_url_scheme( $file_path ); }

			// This is from Gravity Forms's code
			$file_path = esc_attr( str_replace( " ", "%20", $file_path ) );


			/**
			 * @filter `gravityview/fields/fileupload/file_path` Modify the file path before generating a link to it
			 * @since 1.22.3
			 * @since 2.0 Added $context parameter
			 * @param string $file_path Path to the file uploaded by Gravity Forms
			 * @param array  $field_settings Array of GravityView field settings
			 * @param \GV\Template_Context $context The context.
			 */
			$file_path = apply_filters( 'gravityview/fields/fileupload/file_path', $file_path, $field_settings, $context );

			// Get file path information
			$file_path_info = pathinfo( $file_path );

			// If the field is set to link to the single entry, link to it.
			$link = !empty( $field_settings['show_as_link'] ) ? GravityView_API::entry_link( $entry, $field, $base_id ) : $file_path;

			$html_format = NULL;

			$disable_lightbox = false;

			$disable_wrapped_link = false;

			$image_atts = array(
				'src' => $file_path,
				'class' => 'gv-image gv-field-id-'.$field_settings['id'],
				'alt' => $field_settings['label'],
				'width' => ( $is_single ? NULL : 250 )
			);

			/**
			 * Modify the default image attributes for uploaded images
			 * @since 2.0
			 * @see GravityView_Image For the available attributes
			 * @param array $image_atts
			 */
			$image_atts = apply_filters( 'gravityview/fields/fileupload/image_atts', $image_atts );

			// Is this an image?
			$image = new GravityView_Image( $image_atts );

			$content = $image->html();

			// The new default content is the image, if it exists. If not, use the file name as the content.
			$content = !empty( $content ) ? $content : $file_path_info['basename'];

			// If pathinfo() gave us the extension of the file, run the switch statement using that.
			$extension = empty( $file_path_info['extension'] ) ? NULL : strtolower( $file_path_info['extension'] );


			switch( true ) {

				// Audio file
				case in_array( $extension, wp_get_audio_extensions() ):

					$disable_lightbox = true;

					if( shortcode_exists( 'audio' ) ) {

						$disable_wrapped_link = true;

						/**
						 * @filter `gravityview_audio_settings` Modify the settings passed to the `wp_video_shortcode()` function
						 * @since  1.2
						 * @param array $audio_settings Array with `src` and `class` keys
						 * @since 2.0
						 * @param \GV\Template_Context $context The context.
						 */
						$audio_settings = apply_filters( 'gravityview_audio_settings', array(
							'src' => $file_path,
							'class' => 'wp-audio-shortcode gv-audio gv-field-id-'.$field_settings['id']
						), $context );

						/**
						 * Generate the audio shortcode
						 * @see http://codex.wordpress.org/Audio_Shortcode
						 * @see https://developer.wordpress.org/reference/functions/wp_audio_shortcode/
						 */
						$content = wp_audio_shortcode( $audio_settings );

					}

					break;

				// Video file
				case in_array( $extension, wp_get_video_extensions() ):

					$disable_lightbox = true;

					if( shortcode_exists( 'video' ) ) {

						$disable_wrapped_link = true;

						/**
						 * @filter `gravityview_video_settings` Modify the settings passed to the `wp_video_shortcode()` function
						 * @since  1.2
						 * @param array $video_settings Array with `src` and `class` keys
						 * @since 2.0
						 * @param \GV\Template_Context $context The context.
						 */
						$video_settings = apply_filters( 'gravityview_video_settings', array(
							'src' => $file_path,
							'class' => 'wp-video-shortcode gv-video gv-field-id-'.$field_settings['id']
						), $context );

						/**
						 * Generate the video shortcode
						 * @see http://codex.wordpress.org/Video_Shortcode
						 * @see https://developer.wordpress.org/reference/functions/wp_video_shortcode/
						 */
						$content = wp_video_shortcode( $video_settings );

					}

					break;

				// PDF
				case $extension === 'pdf':

					// PDF needs to be displayed in an IFRAME
					$link = add_query_arg( array( 'TB_iframe' => 'true' ), $link );

					break;

				// if not image, do not set the lightbox (@since 1.5.3)
				case !in_array( $extension, array( 'jpg', 'jpeg', 'jpe', 'gif', 'png' ) ):

					$disable_lightbox = true;

					break;

			}

			// If using Link to File, override the content.
			// (We do this here so that the $disable_lightbox can be set. Yes, there's a little more processing time, but oh well.)
			if( !empty( $field_settings['link_to_file'] ) ) {

				// Force the content to be the file name
				$content =  $file_path_info["basename"];

				// Restore the wrapped link
				$disable_wrapped_link = false;

			}

			// Whether to use lightbox or not
			if ( $disable_lightbox || ! $lightbox || ! empty( $field_settings['show_as_link'] ) ) {

				$link_atts = empty( $field_settings['show_as_link'] ) ? array( 'target' => '_blank' ) : array();

			} else {

				$entry_slug = GravityView_API::get_entry_slug( $entry['id'], $entry );

				$link_atts = array(
					'rel' => sprintf( "%s-%s", $gv_class, $entry_slug ),
					'class' => 'thickbox',
				);

			}

			/**
			 * @filter `gravityview/fields/fileupload/link_atts` Modify the link attributes for a file upload field
			 * @param array|string $link_atts Array or attributes string
			 * @param array $field_compat Current GravityView field array
			 * @since 2.0
			 * @param \GV\Template_Context $context The context.
			 */
			$link_atts = apply_filters( 'gravityview/fields/fileupload/link_atts', $link_atts, $field_compat, $context );

			/**
			 * @filter `gravityview/fields/fileupload/disable_link` Filter to alter the default behaviour of wrapping images (or image names) with a link to the content object
			 * @since 1.5.1
			 * @param bool $disable_wrapped_link whether to wrap the content with a link to the content object.
			 * @param array $field_compat Current GravityView field array
			 * @see GravityView_API:field_value() for info about $gravityview_view->field_data
			 * @since 2.0
			 * @param \GV\Template_Context $context The context.
			 */
			$disable_wrapped_link = apply_filters( 'gravityview/fields/fileupload/disable_link', $disable_wrapped_link, $field_compat, $context );

			// If the HTML output hasn't been overridden by the switch statement above, use the default format
			if ( !empty( $content ) && empty( $disable_wrapped_link ) ) {

				/**
				 * Modify the link text (defaults to the file name)
				 *
				 * @since 1.7
				 *
				 * @param string $content The existing anchor content. Could be `<img>` tag, audio/video embed or the file name
				 * @param array $field_compat Current GravityView field array
				 * @since 2.0
				 * @param \GV\Template_Context $context The context.
				 */
				$content = apply_filters( 'gravityview/fields/fileupload/link_content', $content, $field_compat, $context );

                $content = gravityview_get_link( $link, $content, $link_atts );
			}

			$output_arr[] = array(
				'file_path' => $file_path,
				'content' => $content
			);

		} // End foreach loop

		/**
		 * @filter `gravityview/fields/fileupload/files_array` Modify the files array
		 * @since 1.7
		 * @param array $output_arr Associative array of files \n
		 *  @type string $file_path The path to the file as stored in Gravity Forms \n
		 *  @type string $content The generated output for the file \n
		 * @param array $field_compat Current GravityView field array
		 * @since 2.0
		 * @param \GV\Template_Context $context The context.
		 */
		$output_arr = apply_filters( 'gravityview/fields/fileupload/files_array', $output_arr, $field_compat, $context );

		return $output_arr;
	}
}

new GravityView_Field_FileUpload;
