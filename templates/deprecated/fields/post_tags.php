<?php
/**
 * Display the post_tags field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

if ( ! empty( $field_settings['dynamic_data'] ) && ! empty( $entry['post_id'] ) ) {

	$term_list = gravityview_get_the_term_list( $entry['post_id'], $field_settings['link_to_term'] );

	if ( empty( $term_list ) ) {
		do_action( 'gravityview_log_debug', 'Dynamic data for post #' . $entry['post_id'] . ' doesnt exist.' );
	}

	echo $term_list;

} elseif ( empty( $field_settings['link_to_term'] ) ) {


		echo $display_value;

} else {

	echo gravityview_convert_value_to_term_list( $display_value );
}
