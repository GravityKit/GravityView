<?php

global $gravityview_view;

extract( $gravityview_view->field_data );

if( !empty( $field_settings['dynamic_data'] ) ) {
	echo gravityview_get_the_term_list( $entry['post_id'], $field_settings['link_to_term'], 'category');
} else {

	if( empty( $field_settings['link_to_term'] ) ) {

		echo $display_value;

	} else {

		echo gravityview_convert_value_to_term_list( $value, 'category' );
	}
}
