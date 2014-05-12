<?php

global $gravityview_view;

extract( $gravityview_view->__get('field_data') );


/*@list($url, $title, $caption, $description) = $valueArray;

$size = '';
if(!empty($url)){

	$src = $url;
	$size = @getimagesize($src);
	$img = "<img src='$src' {$size[3]}/>";
	$target = '';
	 $img = array(
	 	'src' => $src,
	 	'size' => $size,
	 	'title' => $title,
	 	'caption' => $caption,
	 	'description' => $description,
	 	'url' => esc_attr($url),
	 	'code' => isset($size[3]) ? "<img src='$src' {$size[3]} />" : "<img src='$src' />"
	 );
	 echo $img['code'];
	$lightboxclass = '';
	$output = "<a href='{$url}'{$target}{$lightboxclass}>{$img['code']}</a>";
}*/

$output = "";
if(!empty($value)){
    $output_arr = array();
    $file_paths = rgar($field,"multipleFiles") ? json_decode($value) : array($value);

    foreach($file_paths as $file_path){
        $info = pathinfo($file_path);
        if(GFCommon::is_ssl() && strpos($file_path, "http:") !== false ){
            $file_path = str_replace("http:", "https:", $file_path);
        }
        $file_path = esc_attr(str_replace(" ", "%20", $file_path));

        $output_arr[] = $format == "text" ? $file_path . PHP_EOL: "<li><a href='$file_path' target='_blank' title='" . __("Click to view", "gravityforms") . "'>" . $info["basename"] . "</a></li>";
    }
    $output = join(PHP_EOL, $output_arr);
  }
$output = empty($output) || $format == "text" ? $output : sprintf("<ul>%s</ul>", $output);

echo $output;

