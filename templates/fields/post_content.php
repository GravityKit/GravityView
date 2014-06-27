<?php

global $gravityview_view;

extract( $gravityview_view->field_data );

if( !empty( $field_settings['dynamic_data'] ) ) {
	$post = get_post( $entry['post_id'] );
	echo apply_filters('the_content', $post->post_content);
	wp_reset_postdata();
} else {
	echo $display_value;
}
