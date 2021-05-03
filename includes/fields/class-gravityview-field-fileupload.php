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

	var $icon = 'dashicons-upload';

	public function __construct() {
		$this->label = esc_html__( 'File Upload', 'gravityview' );
		parent::__construct();
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

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

		$add_options['image_width'] = array(
			'type' => 'text',
			'label' => __( 'Custom Width:', 'gravityview' ),
			'desc' => __( 'Override the default image width (250).', 'gravityview' ),
			'value' => '250',
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
	 * Replaces insecure file paths with secure file paths for WordPress media shortcode output
	 *
	 * The WordPress media shortcodes need to be passed insecure file paths so WordPress can parse the extension]
	 * that is being rendered and properly generate the code. Once that shortcode is rendered, we then replace the
	 * insecure file paths with the secure file paths used by Gravity Forms.
	 *
	 * @since 2.10.3
	 *
	 * @param string $rendered The output of the WordPress audio/video shortcodes.
	 * @param string $insecure_file_path Insecure path to the file, showing the directory structure.
	 * @param string $secure_file_path Secure file path using Gravity Forms rewrites.
	 *
	 * @return string HTML output with insecure file paths converted to secure.
	 */
	static private function replace_insecure_wp_shortcode_output( $rendered = '', $insecure_file_path = '', $secure_file_path = '' ) {

		// The shortcode adds instance URL args: add_query_arg( '_', $instance, $atts[ $fallback ] )
		// these break the path, since we already have "?" in the URL
		$rendered = str_replace( '?_=', '&_=', $rendered );

		$rendered = str_replace( esc_attr( $insecure_file_path ), esc_attr( $secure_file_path ), $rendered );
		$rendered = str_replace( esc_html( $insecure_file_path ), esc_html( $secure_file_path ), $rendered );
		$rendered = str_replace( esc_url( $insecure_file_path ), esc_url( $secure_file_path ), $rendered );
		$rendered = str_replace( trim( $insecure_file_path ), trim( $secure_file_path ), $rendered );

		return $rendered;
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

			_doing_it_wrong( __METHOD__, '2.0', 'Please pass a \GV\Template_Context object as the 3rd parameter' );

			$gravityview_view = GravityView_View::getInstance();
			/** @deprecated path */
			$gv_field_array = $gravityview_view->getCurrentField();

			/** @type GF_Field_FileUpload $field */
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

		$field_settings_backup = $field_settings;
		// Process each file path
		foreach ( $file_paths as $index => $file_path ) {

			$rendered = null;

			// If the site is HTTPS, use HTTPS
			if ( function_exists('set_url_scheme') ) {
				$file_path = set_url_scheme( $file_path );
			}

			// This is from Gravity Forms's code
			$file_path = esc_attr( str_replace( " ", "%20", $file_path ) );

			// Get file path information
			$file_path_info = pathinfo( $file_path );

			// If pathinfo() gave us the extension of the file, run the switch statement using that.
			$extension = empty( $file_path_info['extension'] ) ? NULL : strtolower( $file_path_info['extension'] );
			$basename = $file_path_info['basename'];

			// Get the secure download URL
			$is_secure = false;
			$disable_lightbox = false;
			$insecure_file_path = $file_path;
			$secure_file_path = $field->get_download_url( $file_path );
			$text = $basename;

			if ( $secure_file_path !== $file_path ) {
				$basename = basename( $secure_file_path );
				$file_path = $secure_file_path;
				$is_secure = true;
			}

			/**
			 * @filter `gravityview/fields/fileupload/file_path` Modify the file path before generating a link to it
			 * @since 1.22.3
			 * @since 2.0 Added $context parameter
			 * @since 2.8.2
			 * @param string $file_path Path to the file uploaded by Gravity Forms
			 * @param array  $field_settings Array of GravityView field settings
			 * @param \GV\Template_Context $context The context.
			 * @param int $index The current index of the $file_paths array being processed
			 */
			$file_path = apply_filters( 'gravityview/fields/fileupload/file_path', $file_path, $field_settings, $context, $index );

			// Audio
			if ( in_array( $extension, wp_get_audio_extensions() ) ) {
				if ( shortcode_exists( 'audio' ) ) {

					/**
					 * @filter `gravityview_audio_settings` Modify the settings passed to the `wp_video_shortcode()` function
					 * @since  1.2
					 * @param array $audio_settings Array with `src` and `class` keys
					 * @since 2.0
					 * @param \GV\Template_Context $context The context.
					 */
					$audio_settings = apply_filters( 'gravityview_audio_settings', array(
						'src' => $insecure_file_path, // Needs to be insecure path so WP can parse extension
						'class' => 'wp-audio-shortcode gv-audio gv-field-id-'.$field_settings['id']
					), $context );

					/**
					 * Generate the audio shortcode
					 * @see http://codex.wordpress.org/Audio_Shortcode
					 * @see https://developer.wordpress.org/reference/functions/wp_audio_shortcode/
					 */
					$rendered = wp_audio_shortcode( $audio_settings );

					if ( $is_secure ) {
						$rendered = self::replace_insecure_wp_shortcode_output( $rendered, $insecure_file_path, $secure_file_path );
					}
				}

			// Video
			} else if ( in_array( $extension, wp_get_video_extensions() ) ) {

				if ( shortcode_exists( 'video' ) ) {

					/**
					 * @filter `gravityview_video_settings` Modify the settings passed to the `wp_video_shortcode()` function
					 * @since  1.2
					 * @param array $video_settings Array with `src` and `class` keys
					 * @since 2.0
					 * @param \GV\Template_Context $context The context.
					 */
					$video_settings = apply_filters( 'gravityview_video_settings', array(
						'src' => $insecure_file_path, // Needs to be insecure path so WP can parse extension
						'class' => 'wp-video-shortcode gv-video gv-field-id-'.$field_settings['id']
					), $context );

					/**
					 * Generate the video shortcode
					 * @see http://codex.wordpress.org/Video_Shortcode
					 * @see https://developer.wordpress.org/reference/functions/wp_video_shortcode/
					 */
					$rendered = wp_video_shortcode( $video_settings );

					if ( $is_secure ) {
						$rendered = self::replace_insecure_wp_shortcode_output( $rendered, $insecure_file_path, $secure_file_path );
					}
				}

			// PDF or Text
			} else if ( in_array( $extension, array( 'pdf', 'txt' ), true ) ) {

				// Don't add query arg when exporting as CSV
				if ( $context instanceof \GV\Template_Context && ! ( $context->template instanceof \GV\Field_CSV_Template ) ) {
					// File needs to be displayed in an IFRAME
					$file_path = add_query_arg( array( 'gv-iframe' => 'true' ), $file_path );
				}

				$field_settings['link_to_file'] = true;

			// Images
			} else if ( in_array( $extension, array( 'jpg', 'jpeg', 'jpe', 'gif', 'png' ) ) ) {
				$width = \GV\Utils::get( $field_settings, 'image_width', 250 );
				$image_atts = array(
					'src'   => $file_path,
					'class' => 'gv-image gv-field-id-' . $field_settings['id'],
					'alt'   => $field_settings['label'],
					'width' => ( $is_single ? null : ( $width ? $width: 250 ) )
				);

				if ( $is_secure ) {
					$image_atts['validate_src'] = false;
				}

				/**
				 * Modify the default image attributes for uploaded images
				 *
				 * @since 2.0
				 * @see GravityView_Image For the available attributes
				 *
				 * @param array $image_atts
				 */
				$image_atts = apply_filters( 'gravityview/fields/fileupload/image_atts', $image_atts );

				$image = new GravityView_Image( $image_atts );

				$gv_entry = \GV\GF_Entry::from_entry( $entry );

				$entry_slug = $gv_entry->get_slug();

				unset( $gv_entry );

				if ( $lightbox && empty( $field_settings['show_as_link'] ) ) {
					$lightbox_link_atts = array(
						'rel'   => sprintf( "%s-%s", $gv_class, $entry_slug ),
						'class' => '',
					);

					$lightbox_link_atts = apply_filters( 'gravityview/fields/fileupload/link_atts', $lightbox_link_atts, $field_compat, $context );

					$rendered = gravityview_get_link( $file_path, $image->html(), $lightbox_link_atts );
				} else {
					$rendered = $image->html();
				}

				// Show as link should render the image regardless.
				if ( ! empty( $field_settings['show_as_link'] ) ) {
					$text = $rendered;
				}
			}
			// For all other non-media file types (ZIP, for example), always show as a link regardless of setting.
			else {
				$field_settings['link_to_file'] = true;
				$disable_lightbox = true;
			}

			/**
			 * @filter `gravityview/fields/fileupload/disable_link` Filter to alter the default behaviour of wrapping images (or image names) with a link to the content object
			 * @since 1.5.1
			 * @param bool $disable_wrapped_link whether to wrap the content with a link to the content object.
			 * @param array $field_compat Current GravityView field array
			 * @see GravityView_API:field_value() for info about $gravityview_view->field_data
			 * @since 2.0
			 * @param \GV\Template_Context $context The context.
			 */
			$disable_wrapped_link = apply_filters( 'gravityview/fields/fileupload/disable_link', false, $field_compat, $context );

			// Output textualized content where
			if ( ! $disable_wrapped_link && ( ! empty( $field_settings['link_to_file'] ) || ! empty( $field_settings['show_as_link'] ) ) ) {
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
				$content = apply_filters( 'gravityview/fields/fileupload/link_content', $text, $field_compat, $context );

				if ( empty( $field_settings['show_as_link'] ) ) {
					/**
					 * @filter `gravityview/fields/fileupload/link_atts` Modify the link attributes for a file upload field
					 * @since 2.0 Added $context
					 * @since 2.11 Added $additional_details
					 * @param array|string $link_atts Array or attributes string
					 * @param array $field_compat Current GravityView field array
					 * @param \GV\Template_Context $context The context.
					 * @param array $additional_details Array of additional details about the file. {
					 * @type string $file_path URL to file.
					 * @type string $insecure_file_path URL to insecure file.
					 * }
					 */
					$link_atts = apply_filters( 'gravityview/fields/fileupload/link_atts', array( 'target' => '_blank' ), $field_compat, $context, compact( 'file_path', 'insecure_file_path', 'disable_lightbox' ) );

					$content = gravityview_get_link( $file_path, $content, $link_atts );
				}
			} else {
				$content = empty( $rendered ) ? $text : $rendered;
			}

			$output_arr[] = array(
				'file_path' => $file_path,
				'content' => $content
			);

			$field_settings = $field_settings_backup; // reset to default
		} // End foreach loop

		/**
		 * @filter `gravityview/fields/fileupload/files_array` Modify the files array
		 * @since 1.7
		 * @since 2.0 Added $context
		 * @param array $output_arr Associative array of files. {
		 *  @type string $file_path The path to the file as stored in Gravity Forms.
		 *  @type string $content The generated output for the file.
		 * }
		 * @param array $field_compat Current GravityView field array.
		 * @param \GV\Template_Context $context The context.
		 */
		$output_arr = apply_filters( 'gravityview/fields/fileupload/files_array', $output_arr, $field_compat, $context );

		return $output_arr;
	}
}

new GravityView_Field_FileUpload;
