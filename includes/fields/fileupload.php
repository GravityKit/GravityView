<?php

/**
 * Add custom options for date fields
 */
class GravityView_Field_FileUpload extends GravityView_Field {

	var $name = 'fileupload';

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
	 * Return an array of files prepared for output.
	 *
	 * Processes files by file type and generates unique output for each.
	 *
	 * Returns array for each file, with the following keys:
	 *
	 * `file_path` => The file path of the file, with a line break
	 * `html` => The file output HTML formatted
	 *
	 * @since  1.2
	 * @todo  Support `playlist` shortcode for playlist of video/audio
	 * @usedby gravityview_get_files_array()
	 * @param  string $value    Field value passed by Gravity Forms. String of file URL, or serialized string of file URL array
	 * @param  string $gv_class Field class to add to the output HTML
	 * @return array           Array of file output, with `file_path` and `html` keys (see comments above)
	 */
	static function get_files_array( $value, $gv_class ) {

		$gravityview_view = GravityView_View::getInstance();

		extract( $gravityview_view->getCurrentField() );

		$output_arr = array();

		// Get an array of file paths for the field.
		$file_paths = rgar( $field , 'multipleFiles' ) ? json_decode( $value ) : array( $value );

		// Process each file path
		foreach( $file_paths as $file_path ) {

			// If the site is HTTPS, use HTTPS
			if(function_exists('set_url_scheme')) { $file_path = set_url_scheme($file_path); }

			// This is from Gravity Forms
			$file_path = esc_attr(str_replace(" ", "%20", $file_path));

			// If the field is set to link to the single entry, link to it.
			$link = !empty( $field_settings['show_as_link'] ) ? GravityView_API::entry_link( $entry, $field ) : $file_path;

			// Get file path information
			$file_path_info = pathinfo($file_path);

			$html_format = NULL;

			$disable_lightbox = false;

			$disable_wrapped_link = false;

			// Is this an image?
			$image = new GravityView_Image(array(
				'src' => $file_path,
				'class' => 'gv-image gv-field-id-'.$field_settings['id'],
				'alt' => $field_settings['label'],
				'width' => (gravityview_get_context() === 'single' ? NULL : 250)
			));

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
						 * Modify the settings passed to the `wp_video_shortcode()` function
						 *
						 * @since  1.2
						 * @var array
						 */
						$audio_settings = apply_filters( 'gravityview_audio_settings', array(
							'src' => $file_path,
							'class' => 'wp-audio-shortcode gv-audio gv-field-id-'.$field_settings['id']
						));

						/**
						 * Generate the audio shortcode
						 * @link http://codex.wordpress.org/Audio_Shortcode
						 * @link https://developer.wordpress.org/reference/functions/wp_audio_shortcode/
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
						 * Modify the settings passed to the `wp_video_shortcode()` function
						 *
						 * @since  1.2
						 * @var array
						 */
						$video_settings = apply_filters( 'gravityview_video_settings', array(
							'src' => $file_path,
							'class' => 'wp-video-shortcode gv-video gv-field-id-'.$field_settings['id']
						));

						/**
						 * Generate the video shortcode
						 * @link http://codex.wordpress.org/Video_Shortcode
						 * @link https://developer.wordpress.org/reference/functions/wp_video_shortcode/
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
			if( $disable_lightbox || empty( $gravityview_view->atts['lightbox'] ) || !empty( $field_settings['show_as_link'] ) ) {

				$link_atts = empty( $field_settings['show_as_link'] ) ? "target='_blank'" : '';

				$link_atts = apply_filters( 'gravityview/fields/fileupload/link_atts', $link_atts, $gravityview_view->getCurrentField() );

			} else {

				$link_atts = sprintf( "rel='%s-{$entry['id']}' class='thickbox' target='_blank'", $gv_class );

			}

			/**
			 * Filter to alter the default behaviour of wrapping images (or image names) with a link to the content object
			 *
			 * @since 1.5.1
			 *
			 * @param bool $disable_wrapped_link whether to wrap the content with a link to the content object.
			 * @param array $gravityview_view->field_data
			 *
			 * @see GravityView_API:field_value() for info about $gravityview_view->field_data
			 *
			 */
			$disable_wrapped_link = apply_filters( 'gravityview/fields/fileupload/disable_link', $disable_wrapped_link, $gravityview_view->getCurrentField() );

			// If the HTML output hasn't been overridden by the switch statement above, use the default format
			if( !empty( $content ) && empty( $disable_wrapped_link ) ) {

				/**
				 * Modify the link text (defaults to the file name)
				 *
				 * @since 1.7
				 *
				 * @param string $content The existing anchor content. Could be `<img>` tag, audio/video embed or the file name
				 * @param array $field GravityView array of the current field being processed
				 */
				$content = apply_filters( 'gravityview/fields/fileupload/link_content', $content, $gravityview_view->getCurrentField() );

                $content = '<a href="'. esc_url( $link ) .'" '. $link_atts .'>'. $content .'</a>';

			}

			$output_arr[] = array(
				'file_path' => $file_path,
				'content' => $content
			);

		} // End foreach loop

		/**
		 * Modify the files array
		 *
		 * @since 1.7
		 *
		 * @param array $output_arr Associative array of files {
		 *      @type string $file_path The path to the file as stored in Gravity Forms
		 *      @type string $content The generated output for the file
		 * }
		 * @param array $field GravityView array of the current field being processed
		 */
		$output_arr = apply_filters( 'gravityview/fields/fileupload/files_array', $output_arr, $gravityview_view->getCurrentField() );

		return $output_arr;
	}

}

new GravityView_Field_FileUpload;
