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

	public static function get_field_input( $field, $value = '', $lead_id = 0, $form_id = 0, $form = null ){

		// Check if we need to use this hack. Ideally, no.
		switch( RGFormsModel::get_input_type( $field ) ){

			// We need to take control of this file type.
			case 'fileupload':
			break;

			// We have no problem with you, other field input babies.
			default:

				// We override the is_post_field validation message because:
				// 1. We don't like the default message much;
				// 2. We want it to be translatable, where the Gravity Forms one isn't
				// 3. We wanted to check whether the current user could edit the post in question.
				$lead = RGFormsModel::get_lead($lead_id);

				if( is_numeric( $lead["post_id"] ) && parent::is_post_field( $field ) ){
					if( false === current_user_can( 'edit_post', $lead["post_id"] ) ) {
				    	return __('You don&rsquo;t have permission to edit this post.', 'gravityview');
				    } else {
				    	return sprintf( __('You can %sedit this post%s from the post page.', 'gravityview'), "<a href='".admin_url("post.php?action=edit&amp;post={$lead["post_id"]}")."''>", '</a>' );
				    }
				}

				return parent::get_field_input( $field, $value, $lead_id, $form_id );
				break;
		}

        $id = $field["id"];
        $field_id = "input_" . $form_id . "_$id";

        $class = rgar($field, "size");
        $class_suffix = "";

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
