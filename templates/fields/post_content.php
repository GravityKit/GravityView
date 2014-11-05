<?php

global $gravityview_view;

extract( $gravityview_view->field_data );

if( !empty( $field_settings['dynamic_data'] ) ) {
	$post = get_post( $entry['post_id'] );

	if( empty( $post ) ) {
		do_action('gravityview_log_debug', 'Dynamic data for post #'.$entry['post_id'].' doesnt exist.' );
		return;
	}

	setup_postdata( $post );
	the_content();
	wp_reset_postdata();
} else {
	echo $display_value;
}
