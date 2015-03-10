<?php

/**
 * We simply cannot abide not being able to edit images on the front end.
 *
 * So we resort to hacking "Core" files. Sorry, @best_practices. Hey, at least we're extending the class! :P
 *
 * @todo Consider supporting other field types
 *
 *
 */
class GV_GFCommon extends GFCommon {

	/**
	 * Generate the output for post fields inputs.
	 *
	 * We need to override the default display because the default display only handles the submitted data, not live data from the created post.
	 *
	 * @since 1.7
	 *
	 * @param $field
	 * @param $value
	 * @param $input_type
	 * @param $entry
	 *
	 * @return array|mixed|string|void
	 */
	static function get_post_value( $field, $value, $input_type, $entry ) {

		// First, make sure they have the capability to edit the post.
		if( false === current_user_can( 'edit_post', $entry["post_id"] ) ) {

			/**
			 * @param string $message The existing "You don't have permission..." text
			 */
			$message = apply_filters('gravityview/edit_entry/unsupported_post_field_text', __('You don&rsquo;t have permission to edit this post.', 'gravityview') );

			return $message;
		}

		$entry_post = get_post( $entry['post_id'] );

		// Post doesn't exist.
		if( $entry_post === null ) {
			return apply_filters('gravityview/edit_entry/no_post_text', __('This field is not editable; the post no longer exists.', 'gravityview' ) );
		}

		$unsupported_field = sprintf( __('You can %sedit this value%s from the post page.', 'gravityview'), "<a href='".admin_url("post.php?action=edit&amp;post={$entry["post_id"]}")."''>", '</a>' );

		/**
		 * @param string $unsupported_field The existing "Edit from Post Page" text
		 * @param array $field The current field being edited
		 * @param array $entry The current entry being edited
		 * @param WP_Post $entry_post The post connected to the current entry
		 */
		$unsupported_field = apply_filters('gravityview/edit_entry/unsupported_post_field_text', $unsupported_field, $field, $entry, $entry_post );

		$field_object_or_array = class_exists( 'GF_Fields' ) ? GF_Fields::create( $field ) : $field;

		switch( $field['type'] ) {

			// @todo Too tough to handle in V1 of edit post fields
			case 'post_image':
				return $unsupported_field;
				break;

			case 'post_title':
			case 'post_content':
			case 'post_excerpt':
				$value = $entry_post->{$input_type};
				$field_type_1_8 = ( $field['type'] === 'post_title' ) ? 'text' : 'textarea';
				break;

			case 'post_category':

				// Get the post's current category IDs as an array
				$value = wp_get_post_categories( $entry['post_id'] );

				// We need to pre-fill the choices, otherwise they will be empty if the form hasn't been submitted.
				$field_object_or_array = GFCommon::add_categories_as_choices( $field_object_or_array, $value );

				// Depending on the input type for post category, we need to pass it in differently
				switch( $input_type ) {

					/**
					 * If radio, we only want the first category from the array.
					 *
					 * For some reason, other single cat. input types handle this okay, but not radios.
					 */
					case 'radio':
						$value = !empty( $value ) ? array_shift( $value ) : '';
						break;

					/**
					 * We need to generate an array where the keys are the input IDs and the values are the category IDs
					 * @see GF_Field_Checkbox::get_checkbox_choices() for code inspiration
					 */
					case 'checkbox':

						/**
						 * Standardize field into array, since GF 1.9
						 */
						$field_array = GVCommon::get_field_array( $field_object_or_array );

						$post_categories = $value;

						$value = array();

						$choice_number = 1;
						foreach ( $field_array['choices'] as $choice ) {

							if ( $choice_number % 10 == 0 ) { //hack to skip numbers ending in 0. so that 5.1 doesn't conflict with 5.10
								$choice_number ++;
							}

							if ( in_array( $choice['value'], $post_categories ) ) {
								$input_id = $field_array['id'] . '.' . $choice_number;
								$value[ $input_id ] = $choice['value'];
							}

							$choice_number ++;
						}
						break;
				}

				$field_object_or_array = GFCommon::add_categories_as_choices( $field_object_or_array, $value );

				break;
			case 'post_custom_field':

				/**
				 * Standardize field into array, since GF 1.9
				 */
				$field_array = GVCommon::get_field_array( $field_object_or_array );

				$meta_name = $field_array['postCustomFieldName'];

				$value = get_post_meta( $entry['post_id'], $meta_name, true );

				// Only certain custom field types are supported
				if( in_array( $input_type, array( 'list', 'fileupload' ) ) ) {
					return $unsupported_field;
				}

				break;
			case 'post_tags':

				$field_type_1_8 = 'text';

				$post_tags = wp_get_post_tags( $entry['post_id'] );

				// Get the tags as an array with the value set to the `name` key and the key set to the `term_id`
				$tags = wp_list_pluck( $post_tags, 'name', 'term_id' );

				// Convert into a CSV
				$value = implode(', ', $tags );

				break;
		}

		$form = GFAPI::get_form( $entry['form_id'] );

		// Have the Post Content field display using rich text editor
		if( $field['type'] === 'post_content' ) {

			/**
			 * Modify the settings passed to wp_editor()
			 * @see wp_editor()
			 */
			$editor_settings = apply_filters( 'gravityview/edit_entry/post_content/wp_editor_settings', array(
				'media_buttons' => false,
				'textarea_rows' => 15,
				'quicktags' => false
			));

			// Get the editor output
			ob_start();
			wp_editor( $value, 'input_'.$field['id'], $editor_settings );
			$value = ob_get_clean();

		} else {

			// Otherwise, hand of input generation to Gravity Forms

			if( is_object( $field_object_or_array ) ) {
				// Gravity Forms 1.9+
				$value = $field_object_or_array->get_field_input( $form, $value, $entry );
			} else {

				$field = GVCommon::get_field_array( $field_object_or_array );

				$field['type'] = isset( $field_type_1_8 ) ? $field_type_1_8 : $input_type;

				// 1.9 backward compatibility
				$value = GFCommon::get_field_input( $field, $value, $entry['id'], $form['id'] );
			}

		}

		return $value;
	}

	public static function get_field_input( $field, $value = '', $lead_id = 0, $form_id = 0, $form = null ){

		// We override the is_post_field validation message because:
		// 1. We don't like the default message much;
		// 2. We want it to be translatable, where the Gravity Forms one isn't
		// 3. We wanted to check whether the current user could edit the post in question.
		$lead = RGFormsModel::get_lead($lead_id);

		$input_type = RGFormsModel::get_input_type( $field );

		// Check if we need to use this hack. Ideally, no.
		switch( $field['type'] ){

			// We need to take control of this file type.
			case 'fileupload':
				break;

			// We have no problem with you, other field input babies.
			default:

				if( is_numeric( $lead["post_id"] ) && parent::is_post_field( $field ) ){

					/**
					 * Handle post field inputs
					 * @since 1.7
					 */
					return self::get_post_value( $field, $value, $input_type, $lead );
				}

				return parent::get_field_input( $field, $value, $lead_id, $form_id );
				break;
		}

        $id = $field["id"];
        $field_id = "input_" . $form_id . "_$id";

        $class = rgar($field, "size");

        $field_input = apply_filters("gform_field_input", "", $field, $value, $lead_id, $form_id);

        if( $field_input ) {
            return $field_input;
        }

        switch( RGFormsModel::get_input_type( $field ) ){

			case 'fileupload':

			    $tabindex = self::get_tabindex();
			    $multiple_files = rgar( $field, "multipleFiles" );
			    $file_list_id = "gform_preview_" . $form_id . "_". $id;
			    $max_upload_size = ! IS_ADMIN && isset( $field["maxFileSize"] ) && $field["maxFileSize"] > 0 ? $field["maxFileSize"] * 1048576: wp_max_upload_size();

			    if( empty( $multiple_files ) ) {

			    	$upload = sprintf("<input type='hidden' name='MAX_FILE_SIZE' value='%d' />", $max_upload_size);
			    	$upload .= sprintf("<input name='input_%d' id='%s' type='file' class='%s' $tabindex />", $id, $field_id, esc_attr($class), 'disabled="disabled"');

			    } else {

			        $upload_action_url = trailingslashit(site_url()) . "?gf_page=upload";
			        $max_files = isset($field["maxFiles"]) && $field["maxFiles"] > 0 ? $field["maxFiles"]: 0;
			        $browse_button_id = 'gform_browse_button_' . $form_id . "_" . $id;
			        $container_id = 'gform_multifile_upload_' . $form_id . "_" . $id;
			        $drag_drop_id = 'gform_drag_drop_area_' . $form_id . "_" . $id;

			        $messages_id = "gform_multifile_messages_{$form_id}_{$id}";
			        $allowed_extensions = isset($field["allowedExtensions"]) && !empty($field["allowedExtensions"]) ? join(",", GFCommon::clean_extensions(explode(",", strtolower($field["allowedExtensions"])))) : array();
			        if(empty($allowed_extensions))
			            $allowed_extensions="*";
			        $disallowed_extensions = GFCommon::get_disallowed_file_extensions();

			        if( defined('DOING_AJAX') && DOING_AJAX && "rg_change_input_type" === rgpost('action')){
			            $plupload_init = array();
			        } else {
			            $plupload_init = array(
			                'runtimes' => 'html5,flash,html4',
			                'browse_button' => $browse_button_id,
			                'container' => $container_id,
			                'drop_element' => $drag_drop_id,
			                'filelist' => $file_list_id,
			                'unique_names' => true,
			                'file_data_name' => 'file',
			                /*'chunk_size' => '10mb',*/ // chunking doesn't currently have very good cross-browser support
			                'url' => $upload_action_url,
			                'flash_swf_url' => includes_url('js/plupload/plupload.flash.swf'),
			                'silverlight_xap_url' => includes_url('js/plupload/plupload.silverlight.xap'),
			                'filters' => array(
			                    'mime_types' => array(array('title' => __( 'Allowed Files', 'gravityview' ), 'extensions' => $allowed_extensions)),
			                    'max_file_size' => $max_upload_size . 'b'
			                ),
			                'multipart' => true,
			                'urlstream_upload' => false,
			                'multipart_params' => array(
			                    "form_id" => $form_id,
			                    "field_id" => $id
			                ),
			                'gf_vars' => array(
			                    'max_files' => $max_files,
			                    'message_id' => $messages_id,
			                    'disallowed_extensions' => $disallowed_extensions
			                )
			            );

			            // plupload 2 was introduced in WordPress 3.9. Plupload 1 accepts a slightly different init array.
			            if (version_compare(get_bloginfo('version'), "3.9-RC1", "<")) {
			                $plupload_init['max_file_size'] = $max_upload_size . 'b';
			                $plupload_init['filters']       = array(array('title' => __('Allowed Files', 'gravityview'), 'extensions' => $allowed_extensions));
			            }
			        }


			        $plupload_init = apply_filters("gform_plupload_settings_{$form_id}", apply_filters('gform_plupload_settings', $plupload_init, $form_id, $field), $form_id, $field);

			        // Multi-file uploading doesn't currently work in iOS Safari,
			        // single-file allows the built-in camera to be used as source for images
			        if ( wp_is_mobile() )
			            $plupload_init['multi_selection'] = false;

			        $plupload_init_json = htmlspecialchars(json_encode($plupload_init), ENT_QUOTES, 'UTF-8');
			        $upload = sprintf("<div id='%s' data-settings='%s' class='gform_fileupload_multifile'><div id='%s' class='gform_drop_area'><span class='gform_drop_instructions'>%s </span><input id='%s' type='button' value='%s' class='button gform_button_select_files'/></div></div>",$container_id, $plupload_init_json, $drag_drop_id, __('Drop files here or' ,'gravityview'), $browse_button_id, __('Select files', 'gravityview') ) ;

			        // Display plupload messages here
			        $upload .= "<div class='validation_message'><ul id='{$messages_id}'></ul></div>";
			        // Add the hidden field used by gravityforms.js to fetch what uploads are in this field.
			        $upload .= sprintf('<input type="hidden" name="input_%d" value=\'%s\' />', $id, esc_attr( $value ) );

			    }

			    // There's a JSON-encoded array of files
			    if( !empty( $value ) ){ // edit entry

			    	// Single upload fields are hidden if they have files; when deleting
			    	// existing files, the field slides down.
			        $upload_display = $multiple_files ? "" : "style='display:none'";

			        $file_urls = $multiple_files ? json_decode($value) : array($value);
			        $preview = "<div id='upload_$id' {$upload_display}>$upload</div>";
			        $preview .= sprintf("<div id='%s'></div>", $file_list_id);
			        $preview .= sprintf("<div id='preview_existing_files_%d'>", $id);

			        foreach( (array)$file_urls as $file_index => $file_url){
			            if(self::is_ssl() && strpos($file_url, "http:") !== false ){
			                $file_url = str_replace("http:", "https:", $file_url);
			            }
			            $file_url = esc_attr($file_url);
			            $preview .= sprintf("<div id='preview_file_%d' class='ginput_preview'><a href='%s' target='_blank' alt='%s' title='%s'>%s</a><a href='%s' target='_blank' alt='" . __('Download file', 'gravityview') . "' title='" . __('Download file', 'gravityview') . "'><span class='dashicons dashicons-download'></span></a><a href='javascript:void(0);' alt='" . __('Delete file', 'gravityview') . "' title='" . __('Delete file', 'gravityview') . "' onclick='DeleteFile(%d,%d,this);' ><span class='dashicons dashicons-dismiss'></span></a></div>", $file_index, $file_url, $file_url, $file_url, GFCommon::truncate_url($file_url), $file_url, $lead_id, $id );
			        }

			        $preview .="</div>";

			        return $preview;
			    } else {

			    	// Otherwise, show the upload form
					$preview = $multiple_files ? sprintf("<div id='%s'></div>", $file_list_id) : "";
                    return "<div class='ginput_container'>$upload</div>" . $preview;

			    }

			break;
		}
	}

}
