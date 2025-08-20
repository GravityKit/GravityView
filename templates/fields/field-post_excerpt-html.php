<?php
/**
 * The default post_excerpt field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

// Get the post ID from entry (supports both standard GF and Advanced Post Creation)
$entry   = $gravityview->entry->as_entry();
$post_id = GVCommon::get_post_id_from_entry( $entry );

if ( ! empty( $gravityview->field->dynamic_data ) && ! empty( $post_id ) ) {

	global $post;

	/** Backup! */
	$_the_post = $post;

	$post = get_post( $post_id );

	if ( empty( $post ) ) {
		gravityview()->log->error( 'Dynamic data for post {post_id} does not exist.', array( 'post_id' => $post_id ) );
		$post = $_the_post;
		return;
	}

	setup_postdata( $post );
	the_excerpt();
	wp_reset_postdata();

	/** Restore! */
	$post = $_the_post;

} else {
	echo $gravityview->display_value;
}
