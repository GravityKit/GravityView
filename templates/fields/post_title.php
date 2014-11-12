<?php

global $gravityview_view;

extract( $gravityview_view->field_data );

if( !empty( $field_settings['dynamic_data'] ) ) {
	$output = get_the_title( $entry['post_id'] );

	if( empty( $output ) ) {
		do_action('gravityview_log_debug', 'Dynamic data for post #'.$entry['post_id'].' doesnt exist.' );
	}

} else {
	$output = $display_value;
}

// Link to the post URL?
if( !empty( $field_settings['link_to_post'] )) {
	echo '<a href="'.get_permalink( $entry['post_id'] ).'">'.esc_attr( $output ).'</a>';
} else {
	echo $output;
}
