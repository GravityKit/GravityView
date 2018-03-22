<?php
/**
 * Display the post_id field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

// Link to the post URL?
if( $gravityview_view->getCurrentFieldSetting('link_to_post') && !empty( $entry['post_id'] ) ) {

	echo gravityview_get_link( get_permalink( $entry['post_id'] ), esc_attr( $display_value ) );

} else {

	echo $display_value;

}
