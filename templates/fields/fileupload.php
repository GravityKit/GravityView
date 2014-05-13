<?php
/**
 * Display the fileupload field type
 *
 * @package GravityView
 */

global $gravityview_view;

extract( $gravityview_view->__get('field_data') );

$output = "";
if(!empty($value)){
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
	    $html_format = sprintf("<a href='$file_path' rel='%s' target='_blank' title='" . __("Click to view", "gravityforms") . "'>" . $content . "</a>", gv_class( $field ));

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
    	$output .= sprintf("<ul class='gv-field-file-uploads %s'>", gv_class( $field ));
    	foreach ($output_arr as $key => $item) {
			$output .= '<li>' . $item['html'] . PHP_EOL .'</li>';
		}
		$output .= '</ul>';
    }

  }

echo $output;

