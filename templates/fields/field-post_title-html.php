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
$apc_feed_id    = isset( $gravityview->field->apc_feed_id ) ? $gravityview->field->apc_feed_id : null;
$post_id        = GVCommon::get_post_id_from_entry( $entry, $apc_feed_id );

if ( ! empty( $field_settings['dynamic_data'] ) && ! empty( $post_id ) ) {
	$output = get_the_title( $post_id );

	if ( empty( $output ) ) {
		do_action( 'gravityview_log_debug', 'Dynamic data for post #' . $post_id . ' doesnt exist.' );
	}
} else {
	$output = $display_value;
}

// Link to the post URL?
if ( ! empty( $field_settings['link_to_post'] ) && ! empty( $post_id ) ) {

	echo gravityview_get_link( get_permalink( $post_id ), esc_html( $output ) );

} else {
	echo $output;
}
