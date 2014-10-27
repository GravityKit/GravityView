<?php

global $gravityview_view;

extract( $gravityview_view->field_data );

if( !empty( $field_settings['dynamic_data'] ) ) {

	$term_list = gravityview_get_the_term_list( $entry['post_id'], $field_settings['link_to_term'], 'category');

	if( empty( $term_list ) ) {
		do_action('gravityview_log_debug', 'Dynamic data for post #'.$entry['post_id'].' doesnt exist.' );
	}

	echo $term_list;

} else {

	if( empty( $field_settings['link_to_term'] ) ) {

		echo $display_value;

	} else {

		echo gravityview_convert_value_to_term_list( $value, 'category' );
	}
}
