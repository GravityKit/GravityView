<?php
/**
 * Display the fileupload field type
 *
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

		// Is this an image?
		$image = new GravityView_Image(array(
			'src' => $file_path,
			'class' => 'gv-image gv-field-id-'.$field_settings['id'],
			'alt' => $field_settings['label'],
			'width' => (gravityview_get_context() === 'single' ? NULL : 250)
		));

		$image_html = $image->html();

		// If so, use it!
		if(!empty($image_html)) {
			$content = $image;
		} else {
			// Otherwise, get a link
			$info = pathinfo($file_path);
			$content = $info["basename"];
		}

		$text_format = $file_path . PHP_EOL;

		switch( $info['extension'] ) {
			case 'mp4':
			case 'ogv':
			case 'ogg':
			case 'webm':
				$incompatible_text = __('Sorry, your browser doesn&rsquo;t support embedded videos, but you can %sdownload it%s and watch it with your favorite video player!', '<a href="'.$file_path.'">', '</a>' );
				$video_tag = '<video controls="controls" preload="auto" width="375"><source src="'.esc_url( $file_path ).'" type="video/'.esc_attr( $info['extension'] ).'" /> '.$incompatible_text.'</video>';
				$html_format = apply_filters( 'gravityview_video_html', $video_tag, $info, $incompatible_text );
				break;
			default:
				$html_format = sprintf("<a href='{$file_path}' rel='%s-{$entry['id']}' class='thickbox' target='_blank'>" . $content . "</a>", $gv_class );
				break;
		}

		$output_arr[] = array(
			'text' => $text_format,
			'html' => $html_format,
			'content' => $content
		);

	} // End foreach

	// If the output array is just one item, let's not show a list.
	if(sizeof($output_arr) === 1) {
		$output = wpautop( $output_arr[0]['html'] );
	} else {
		// Otherwise, a list it is!
		$output .= sprintf("<ul class='gv-field-file-uploads %s'>", $gv_class );
		foreach ($output_arr as $key => $item) {
			$output .= '<li>' . $item['html'] . PHP_EOL .'</li>';
		}
		$output .= '</ul>';
	}

  }

echo $output;

