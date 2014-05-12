<?php
/**
 * Display the website field type
 *
 * @package GravityView
 */

global $gravityview_view;

extract( $gravityview_view->__get('field_data') );

$url_info = parse_url($value);

if(isset($url_info['host']) && apply_filters( 'gravityview_field_website_shorten_url', true )) {
	$anchor_text = $url_info['host'];
	echo "<a href='$value' target='_blank'>$anchor_text</a>";
} else {
	echo $display_value;
}
