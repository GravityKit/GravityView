<?php
/**
 * Display the post_content field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

if( !empty( $field_settings['dynamic_data'] ) && !empty( $entry['post_id'] ) ) {

	global $post;

	/** Backup! */
	$_the_post = $post;

	$post = get_post( $entry['post_id'] );

	if( empty( $post ) ) {
		do_action('gravityview_log_debug', 'Dynamic data for post #'.$entry['post_id'].' doesnt exist.' );
		wp_reset_postdata();
		return;
	}

	setup_postdata( $post );
	the_content();
	wp_reset_postdata();

	/** Restore! */
	$post = $_the_post;

} else {
	echo $display_value;
}
