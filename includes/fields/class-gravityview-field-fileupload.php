<?php

use GV\Template_Context;

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
		$this->label = esc_html__( 'File Upload', 'gk-gravityview' );
		parent::__construct();
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		unset( $field_options['search_filter'] );

		if ( 'edit' === $context ) {
			return $field_options;
		}

		$add_options['link_to_file'] = array(
			'type'       => 'checkbox',
			'label'      => __( 'Display as a Link:', 'gk-gravityview' ),
			'desc'       => __( 'Display the uploaded files as links, rather than embedded content.', 'gk-gravityview' ),
			'value'      => false,
			'merge_tags' => false,
		);

		$add_options['bypass_secure_download'] = array(
			'type'       => 'checkbox',
			'label'      => __( 'Use Direct File Path for Media:', 'gk-gravityview' ),
			'desc'       => __( 'Point to uploaded files directly instead of using secure download URLs. This improves performance.', 'gk-gravityview' ),
			'tooltip'    => __( 'This improves performance when displaying media but reduces file security. Non-embeddable files (PDFs, documents, etc.) will still use secure download paths.', 'gk-gravityview' ),
			'value'      => false,
			'merge_tags' => false,
			'group'      => 'display',
			'article'    => [
				'id'  => '68adf3756587963d509160bd',
				'type' => 'modal',
				'url' => 'https://docs.gravitykit.com/article/1088-direct-file-path',
			],
		);

		$add_options['image_width'] = array(
			'type'       => 'text',
			'label'      => __( 'Custom Width:', 'gk-gravityview' ),
			'desc'       => __( 'Override the default image width (250).', 'gk-gravityview' ),
			'value'      => '250',
			'merge_tags' => false,
		);

		$field = \GV\GF_Field::by_id( \GV\GF_Form::by_id( $form_id ), $field_id );

		// Only allow alt text on single files currently.
		if ( empty( $field->field->multipleFiles ) ) {

			$add_options['alt_text'] = array(
				'type'       => 'text',
				'label'      => __( 'Alternative text', 'gk-gravityview' ),
				'desc'       => __( 'Define an alternative text description of a file. For supported file types only. By default, the field label is used.', 'gk-gravityview' ),
				'value'      => false,
				'merge_tags' => 'force',
				'group'      => 'advanced',
			);

		}

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
	private static function replace_insecure_wp_shortcode_output( $rendered = '', $insecure_file_path = '', $secure_file_path = '' ) {

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
	 * @param  string                           $value    Field value passed by Gravity Forms. String of file URL, or serialized string of file URL array
	 * @param  string                           $gv_class Field class to add to the output HTML
	 *
	 * @since 2.0
	 * @param Template_Context The context.
	 *
	 * @return array           Array of file output, with `file_path` and `html` keys (see comments above)
	 */
	static function get_files_array( $value, $gv_class, $context = null ) {

		if ( $context instanceof Template_Context ) {
			$field          = $context->field->field;
			$field_settings = $context->field->as_configuration();
			$entry          = $context->entry->as_entry();
			$field_value    = $context->value;
			global $post;
			$base_id = $post ? $post->ID : $context->view->ID;

			$is_single = $context->request->is_entry();
			$lightbox  = $context->view->settings->get( 'lightbox', false );

			/** A compatibility array that's required by some of the deprecated filters. */
			$field_compat = array(
				'form'           => ( isset( $context->source->form ) ? $context->source->form : '' ),
				'field_id'       => $context->field->ID,
				'field'          => $field,
				'field_settings' => $field_settings,
				'value'          => $field_value,
				'display_value'  => $context->display_value,
				'format'         => 'html',
				'entry'          => $entry,
				'field_type'     => $context->field->type,
				'field_path'     => ( isset( $context->template->located_template ) ? $context->template->located_template : '' ),
			);
		} else {

			_doing_it_wrong( __METHOD__, '2.0', 'Please pass a \GV\Template_Context object as the 3rd parameter' );

			$gravityview_view = GravityView_View::getInstance();
			/** @deprecated path */
			$gv_field_array = $gravityview_view->getCurrentField();

			/** @type GF_Field_FileUpload $field */
			$field          = \GV\Utils::get( $gv_field_array, 'field' );
			$field_settings = \GV\Utils::get( $gv_field_array, 'field_settings' );
			$entry          = \GV\Utils::get( $gv_field_array, 'entry' );
			$field_value    = \GV\Utils::get( $gv_field_array, 'value' );
			$base_id        = null;

			$is_single    = 'single' === gravityview_get_context();
			$lightbox     = ! empty( $gravityview_view->atts['lightbox'] );
			$field_compat = $gravityview_view->getCurrentField();
		}

		$output_arr = array();

		// Get an array of file paths for the field.
		$file_paths = 1 !== (int) \GV\Utils::get( $field, 'multipleFiles' ) ? array( $value ) : $value;

		// The $value JSON was probably truncated; let's check lead_detail_long.
		if ( ! is_array( $file_paths ) ) {
			$full_value = RGFormsModel::get_lead_field_value( $entry, $field );
			$file_paths = json_decode( $full_value );
		}

		if ( ! is_array( $file_paths ) ) {
			gravityview()->log->error(
				'Field does not have a valid image array. JSON decode may have failed.',
				array(
					'data' => array(
						'$value'       => $value,
						'$field_value' => $field_value,
					),
				)
			);
			return $output_arr;
		}

		$field_settings_backup = $field_settings;
		// Process each file path
		foreach ( $file_paths as $index => $file_path ) {

			if ( empty( $file_path ) || ! is_string( $file_path ) ) {
				continue;
			}

			/**
			 * URL-encode non-Latin characters to comply with RFC 3986.
			 *
			 * @see https://github.com/GravityKit/GravityView/issues/2051
			 * @see https://stackoverflow.com/a/27124836
			 */
			$_file_path = preg_replace_callback( '/[^\x21-\x7f]/', function ( $match ) {
				return rawurlencode( $match[0] );
			}, $file_path );

			// If the file path is not a valid URL, skip it. This is the same check that Gravity Forms does.
			if ( ! GFCommon::is_valid_url( $_file_path ) ) {
				continue;
			}

			$rendered = null;

			$file_info = self::get_file_info( $file_path, $field, $field_settings, $context, $index );

			$file_path          = $file_info['file_path'];
			$basename           = $file_info['basename'];
			$extension          = $file_info['extension'];
			$insecure_file_path = $file_info['insecure_file_path'];
			$secure_file_path   = $file_info['secure_file_path'];
			$is_secure          = $file_info['is_secure'];

			$disable_lightbox = false;
			$text             = $basename;

			$alt = \GV\Utils::get( $field_settings, 'alt_text', '' );
			if ( '' === $alt ) {
				$alt = $field_settings['custom_label'] ?: $field_settings['label'];
			}
			$alt = GFCommon::replace_variables( $alt, GVCommon::get_form( $entry['form_id'] ), $entry );

			// Audio
			if ( in_array( $extension, wp_get_audio_extensions() ) ) {
				if ( shortcode_exists( 'audio' ) ) {

					/**
					 * Modify the default attributes that will be passed to the wp_audio_shortcode() function.
					 *
					 * @since 1.2
					 * @since 2.0 Added $context parameter.
					 *
					 * @param array $audio_settings Array with `src` and `class` keys.
					 * @param Template_Context $context The context.
					 */
					$audio_settings = apply_filters(
						'gravityview_audio_settings',
						array(
							'src'   => $insecure_file_path, // Needs to be insecure path so WP can parse extension
							'class' => 'wp-audio-shortcode gv-audio gv-field-id-' . $field_settings['id'],
						),
						$context
					);

					/**
					 * Generate the audio shortcode.
					 *
					 * @see http://codex.wordpress.org/Audio_Shortcode
					 * @see https://developer.wordpress.org/reference/functions/wp_audio_shortcode/
					 */
					$rendered = wp_audio_shortcode( $audio_settings );

					if ( $is_secure ) {
						$rendered = self::replace_insecure_wp_shortcode_output( $rendered, $insecure_file_path, $secure_file_path );
					}
				}

				// Video
			} elseif ( in_array( $extension, wp_get_video_extensions() ) ) {

				if ( shortcode_exists( 'video' ) ) {

					/**
					 * Modify the default attributes that will be passed to the wp_video_shortcode() function.
					 *
					 * @since 1.2
					 * @since 2.0 Added $context parameter.
					 *
					 * @param array $video_settings Array with `src` and `class` keys
					 * @param Template_Context $context The context.
					 */
					$video_settings = apply_filters(
						'gravityview_video_settings',
						array(
							'src'   => $insecure_file_path, // Needs to be insecure path so WP can parse extension
							'class' => 'wp-video-shortcode gv-video gv-field-id-' . $field_settings['id'],
						),
						$context
					);

					/**
					 * Generate the video shortcode
					 *
					 * @see http://codex.wordpress.org/Video_Shortcode
					 * @see https://developer.wordpress.org/reference/functions/wp_video_shortcode/
					 */
					$rendered = wp_video_shortcode( $video_settings );

					if ( $is_secure ) {
						$rendered = self::replace_insecure_wp_shortcode_output( $rendered, $insecure_file_path, $secure_file_path );
					}
				}

				// PDF or Text
			} elseif ( in_array( $extension, array( 'pdf', 'txt' ), true ) ) {

				// Don't add query arg when exporting as CSV
				if ( $context instanceof Template_Context && ! ( $context->template instanceof \GV\Field_CSV_Template ) ) {
					// File needs to be displayed in an IFRAME
					$file_path = add_query_arg( array( 'gv-iframe' => 'true' ), $file_path );
				}

				$field_settings['link_to_file'] = true;

				// Images
			} elseif ( in_array( $extension, GravityView_Image::get_image_extensions() ) ) {
				$width = \GV\Utils::get( $field_settings, 'image_width', 250 );

				$image_atts = array(
					'src'   => $file_path,
					'class' => 'gv-image gv-field-id-' . $field_settings['id'],
					'alt'   => $alt,
					'width' => ( $is_single ? null : ( $width ? $width : 250 ) ),
				);

				if ( $is_secure ) {
					$image_atts['validate_src'] = false;
				}

				/**
				 * Modify the default image attributes for uploaded images.
				 *
				 * @since 2.0
				 * @see GravityView_Image For the available attributes.
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
						'rel'   => sprintf( '%s-%s', $gv_class, $entry_slug ),
						'class' => '',
					);

					$lightbox_link_atts = apply_filters( 'gravityview/fields/fileupload/link_atts', $lightbox_link_atts, $field_compat, $context, compact( 'file_path', 'insecure_file_path', 'disable_lightbox' ) );

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
				$disable_lightbox               = true;
			}

			/**
			 * Filter to alter the default behaviour of wrapping images (or image names) with a link to the content object.
			 *
			 * @since 1.5.1
			 * @param bool $disable_wrapped_link whether to wrap the content with a link to the content object.
			 * @param array $field_compat Current GravityView field array
			 * @see GravityView_API:field_value() for info about $gravityview_view->field_data
			 * @since 2.0
			 * @param Template_Context $context The context.
			 */
			$disable_wrapped_link = apply_filters( 'gravityview/fields/fileupload/disable_link', false, $field_compat, $context );

			// Output textualized content where
			if ( ! $disable_wrapped_link && ( ! empty( $field_settings['link_to_file'] ) || ! empty( $field_settings['show_as_link'] ) ) ) {
				/**
				 * Modify the link text (defaults to the file name).
				 *
				 * @since 1.7
				 *
				 * @param string $content The existing anchor content. Could be `<img>` tag, audio/video embed or the file name.
				 * @param array $field_compat Current GravityView field array.
				 * @since 2.0
				 * @param Template_Context $context The context.
				 */
				$content = apply_filters( 'gravityview/fields/fileupload/link_content', $text, $field_compat, $context );

				if ( empty( $field_settings['show_as_link'] ) ) {
					/**
					 * Modify the link attributes for a file upload field.
					 *
					 * @since 2.0 Added $context
					 * @since 2.11 Added $additional_details
					 * @param array|string $link_atts Array or attributes string
					 * @param array $field_compat Current GravityView field array
					 * @param Template_Context $context The context.
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
				'content'   => $content,
			);

			$field_settings = $field_settings_backup; // reset to default
		} // End foreach loop

		/**
		 * Modify the files array.
		 *
		 * @since 1.7
		 * @since 2.0 Added $context
		 * @param array $output_arr Associative array of files. {
		 *  @type string $file_path The path to the file as stored in Gravity Forms.
		 *  @type string $content The generated output for the file.
		 * }
		 * @param array $field_compat Current GravityView field array.
		 * @param Template_Context $context The context.
		 */
		$output_arr = apply_filters( 'gravityview/fields/fileupload/files_array', $output_arr, $field_compat, $context );

		return $output_arr;
	}

	/**
	 * Prepares information about the file.
	 *
	 * @since 2.16
	 *
	 * @param string               $file_path The file path as returned from Gravity Forms.
	 * @param GF_Field_FileUpload  $field The file upload field.
	 * @param array                $field_settings GravityView settings for the field {@see \GV\Field::as_configuration()}
	 * @param Template_Context $context
	 * @param int                  $index The index of the current file in the array of files.
	 *
	 * @return array{file_path: string, insecure_file_path: string,secure_file_path: string,basename:string,extension:string,is_secure: bool}
	 */
	private static function get_file_info( $file_path, $field, $field_settings, $context, $index ) {

		// If the site is HTTPS, use HTTPS
		if ( function_exists( 'set_url_scheme' ) ) {
			$file_path = set_url_scheme( $file_path );
		}

		// Get file path information
		$file_path_info = pathinfo( $file_path );

		// If pathinfo() gave us the extension of the file, run the switch statement using that.
		$extension = empty( $file_path_info['extension'] ) ? null : strtolower( $file_path_info['extension'] );

		/**
		 * Modify the file extension before it's used in display logic.
		 *
		 * @since 2.13.5
		 *
		 * @param string $extension The extension of the file, as parsed by `pathinfo()`.
		 * @param string $file_path Path to the file uploaded by Gravity Forms.
		 */
		$extension = apply_filters( 'gravityview/fields/fileupload/extension', $extension, $file_path );

		$basename = $file_path_info['basename'];

		// Get the secure download URL
		$is_secure          = false;
		$insecure_file_path = str_replace( ' ', '%20', $file_path );
		$secure_file_path   = str_replace( ' ', '%20', $field->get_download_url( $file_path ) );

		$bypass_secure_links = self::should_bypass_secure_links( $field_settings, $field, $file_path, $extension, $context );

		// Only use the secure download URL if bypass is disabled AND a secure URL was generated.
		// This preserves the original URL when bypass is enabled or when GF doesn't generate a secure URL.
		if ( ! $bypass_secure_links && $secure_file_path !== $file_path ) {
			$file_path = $secure_file_path;
			$is_secure = true;
		}

		/**
		 * Modify the file path before generating a link to it.
		 *
		 * @since 1.22.3
		 * @since 2.0 Added $context parameter
		 * @since 2.8.2
		 *
		 * @param string $file_path Path to the file uploaded by Gravity Forms
		 * @param array $field_settings Array of GravityView field settings
		 * @param Template_Context $context The context.
		 * @param int $index The current index of the $file_paths array being processed
		 */
		$file_path = apply_filters( 'gravityview/fields/fileupload/file_path', $file_path, $field_settings, $context, $index );

		return array(
			'file_path'          => $file_path,
			'insecure_file_path' => $insecure_file_path,
			'secure_file_path'   => $secure_file_path,
			'basename'           => $basename,
			'extension'          => $extension,
			'is_secure'          => $is_secure,
		);
	}

	/**
	 * Determine if we should bypass secure download URLs for this field.
	 *
	 * @since TODO
	 *
	 * @param array                $field_settings GravityView settings for the field.
	 * @param GF_Field_FileUpload  $field          The file upload field.
	 * @param string               $file_path      The file path.
	 * @param string               $extension      The file extension.
	 * @param Template_Context $context        The template context.
	 *
	 * @return bool Whether to bypass secure download URLs.
	 */
	private static function should_bypass_secure_links( $field_settings, $field, $file_path, $extension, $context ) {
		$bypass_secure_links = \GV\Utils::get( $field_settings, 'bypass_secure_download', false );

		// Only bypass for media files by default (images, audio, video).
		if ( $bypass_secure_links ) {
			$file_extension = strtolower( $extension );

			// Get allowed media extensions.
			$image_extensions = GravityView_Image::get_image_extensions();
			$audio_extensions = wp_get_audio_extensions();
			$video_extensions = wp_get_video_extensions();
			$media_extensions = array_merge( $image_extensions, $audio_extensions, $video_extensions );

			/**
			 * Filters the file extensions that are allowed to bypass secure download URLs.
			 *
			 * By default, only media files (images, audio, video) can bypass secure downloads.
			 * Use this filter to customize which file types are allowed to use direct URLs.
			 *
			 * Special case: Return an array containing '*' to allow ALL file types to bypass
			 * secure downloads. Use with extreme caution as this exposes all uploaded files.
			 *
			 * @filter `gk/gravityview/fields/fileupload/secure-links/allowed-extensions`
			 *
			 * @since TODO
			 *
			 * @param array                $media_extensions Array of file extensions that can bypass secure downloads.
			 *                                               Default: merge of image, audio, and video extensions.
			 *                                               Use array('*') to allow all file types.
			 * @param array                $field_settings   GravityView settings for the field.
			 * @param GF_Field_FileUpload  $field            The file upload field.
			 * @param array                $field_settings   GravityView settings for the field.
			 * @param Template_Context $context          The template context.
			 * @param string               $file_path        The file path.
			 */
			$allowed_extensions = apply_filters( 'gk/gravityview/fields/fileupload/secure-links/allowed-extensions', $media_extensions, $field, $field_settings, $context, $file_path );

			// Only bypass if the file extension is in the allowed list.
			// Special case: '*' means allow all extensions.
			if ( ! in_array( '*', $allowed_extensions, true ) && ! in_array( $file_extension, $allowed_extensions, true ) ) {
				$bypass_secure_links = false;
			}
		}

		/**
		 * Filters whether to bypass secure download URLs for this field.
		 *
		 * @since TODO
		 *
		 * @param bool                $bypass_secure_links Whether to bypass secure download URLs and use direct file paths.
		 * @param GF_Field_FileUpload $field               The file upload field.
		 * @param array               $field_settings      GravityView settings for the field.
		 * @param Template_Context    $context             The template context.
		 * @param string              $file_path           The original file path.
		 */
		$bypass_secure_links = apply_filters( 'gk/gravityview/fields/fileupload/secure-links/bypass', $bypass_secure_links, $field, $field_settings, $context, $file_path );

		return (bool) $bypass_secure_links;
	}
}

new GravityView_Field_FileUpload();
