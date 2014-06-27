<?php
/**
 * Display the fileupload field type
 *
 * @package GravityView
 */

global $gravityview_view;

extract( $gravityview_view->field_data );

$output = $display_value;

if( !empty( $field_settings['show_as_link'] ) || !empty( $field_settings['link_to_post'] ) ) {

	// Strip link to file - we're going to be wrapping with link to entry.
	// DIVs have issues being wrapped by an inline element. Strip them, too.
	$output = strip_tags( $output, '<img><span>');
}

// Link to the post URL?
if( !empty( $field_settings['link_to_post'] )) {
	$output = '<a href="'.get_permalink( $entry['post_id'] ).'">'.esc_attr( $output ).'</a>';
} else {

	// Add thickbox
	$output =  str_replace('<a ', '<a class="thickbox" ', $output );
}

echo $output;
