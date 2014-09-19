<?php
/**
 * Display the fileupload field type
 *
 * @todo Move the logic into a function and include templates for each file type instead, like `fileupload-[image|audio|video|pdf].php`, or `fileupload-[jpg|mp3|pdf|mp4].php`
 * @package GravityView
 */

global $gravityview_view;

extract( $gravityview_view->field_data );

$output = "";
if(!empty($value)){

	$gv_class = gv_class( $field, $gravityview_view->form, $entry );


	$output_arr = array();
	$file_paths = rgar($field,"multipleFiles") ? json_decode($value) : array($value);

	foreach($file_paths as $file_path){

		// If the site is HTTPS, use HTTPS
		if(function_exists('set_url_scheme')) { $file_path = set_url_scheme($file_path); }

		// This is from Gravity Forms
		$file_path = esc_attr(str_replace(" ", "%20", $file_path));

		// If the field is set to link to the single entry, link to it.
		$link = !empty( $field_settings['show_as_link'] ) ? GravityView_API::entry_link( $entry, $field ) : $file_path;

		// Is this an image?
		$image = new GravityView_Image(array(
			'src' => $file_path,
			'class' => 'gv-image gv-field-id-'.$field_settings['id'],
			'alt' => $field_settings['label'],
			'width' => (gravityview_get_context() === 'single' ? NULL : 250)
		));

		$image_html = $image->html();

		// Get file path information
		$file_path_info = pathinfo($file_path);

		$link_atts = ( !empty( $gravityview_view->atts['lightbox'] ) && empty( $field_settings['show_as_link'] ) && empty( $field_settings['link_to_file'] ) ) ? "rel='%s-{$entry['id']}' class='thickbox' target='_blank'" : "target='_blank'";

		// If they want to force linking it to the file, and not embed rich file
		if( !empty( $field_settings['link_to_file'] ) ) {

			$content = $file_path_info["basename"];

			$html_format = sprintf("<a href='{$link}' {$link_atts}>" . $content . "</a>", $gv_class );

		}
		// If this is an image show it!
		else if( !empty( $image_html ) ) {

			$content = $image;

			$html_format = sprintf("<a href='{$link}' {$link_atts}>" . $content . "</a>", $gv_class );

		} else {

			$content = $file_path_info["basename"];
			$extension = empty( $file_path_info['extension'] ) ? NULL : $file_path_info['extension'];

			switch( $extension ) {
				case 'mp4':
				case 'ogv':
				case 'ogg':
				case 'webm':
					// We could use the {@link http://www.videojs.com VideoJS} library in the future
					$incompatible_text = __('Sorry, your browser doesn&rsquo;t support embedded videos, but you can %sdownload it%s and watch it with your favorite video player!', '<a href="'.$file_path.'">', '</a>' );
					$video_tag = '<video controls="controls" preload="auto" width="375"><source src="'.esc_url( $file_path ).'" type="video/'.esc_attr( $file_path_info['extension'] ).'" /> '.$incompatible_text.'</video>';
					$html_format = apply_filters( 'gravityview_video_html', $video_tag, $file_path_info, $incompatible_text );
					break;

				case "pdf":

					// PDF needs to be displayed in an IFRAME
					$link = add_query_arg( array( 'TB_iframe' => 'true' ), $link );

					// break; left out intentionally so it is handled as default

				default:
					$html_format = sprintf("<a href='{$link}' {$link_atts}>" . $content . "</a>", $gv_class );
					break;
			}

		}

		$text_format = $file_path . PHP_EOL;

		$output_arr[] = array(
			'text' => $text_format,
			'html' => $html_format,
			'content' => $content
		);

	} // End foreach

	// If the output array is just one item, let's not show a list.
	if( sizeof($output_arr) === 1 ) {

		$output = $output_arr[0]['html'];

	} else {

		// Otherwise, a list it is!
		$output = '';

		foreach ($output_arr as $key => $item) {
			// Fix empty lists
			if( empty( $item['content'] ) ) { continue; }
			$output .= '<li>' . $item['html'] . PHP_EOL .'</li>';
		}

		$before = sprintf("<ul class='gv-field-file-uploads %s'>", $gv_class );

		$after = '</ul>';

		if( !empty( $output ) ) {
			$output = $before.$output.$after;
		}
	}

  }

echo $output;

