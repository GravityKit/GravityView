<?php
/**
 * The default post_title field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$display_value  = $gravityview->display_value;
$entry          = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

if ( ! empty( $field_settings['dynamic_data'] ) && ! empty( $entry['post_id'] ) ) {
	$output = get_the_title( $entry['post_id'] );

	if ( empty( $output ) ) {
		do_action( 'gravityview_log_debug', 'Dynamic data for post #' . $entry['post_id'] . ' doesnt exist.' );
	}
} else {
	$output = $display_value;
}

// Link to the post URL?
if ( ! empty( $field_settings['link_to_post'] ) && ! empty( $entry['post_id'] ) ) {

	echo gravityview_get_link( get_permalink( $entry['post_id'] ), esc_html( $output ) );

} else {
	echo $output;
}
