<?php
/**
 * The default post_content field output template.
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
$post_id        = GVCommon::get_post_id_from_entry( $entry );

if ( ! empty( $field_settings['dynamic_data'] ) && ! empty( $post_id ) ) {

	global $post, $wp_query;

	/** Backup! */
	$_the_post = $post;

	$post = get_post( $post_id );

	if ( empty( $post ) ) {
		do_action( 'gravityview_log_debug', 'Dynamic data for post #' . $post_id . ' doesnt exist.' );
		$post = $_the_post;
		return;
	}

	setup_postdata( $post );
	$_in_the_loop          = $wp_query->in_the_loop;
	$wp_query->in_the_loop = false;
	the_content(); /** Prevent the old the_content filter from running. @todo Remove this hack along with the old filter. */
	$wp_query->in_the_loop = $_in_the_loop;
	wp_reset_postdata();

	/** Restore! */
	$post = $_the_post;

} else {
	echo $display_value;
}
