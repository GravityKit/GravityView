<?php
/**
 * The default post_id field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$post_id       = GVCommon::get_post_id_from_entry( $gravityview->entry->as_entry() );
$display_value = $gravityview->display_value ? $gravityview->display_value : $post_id;

if ( $gravityview->field->link_to_post && ! empty( $post_id ) ) {
	echo gravityview_get_link( get_permalink( $post_id ), esc_html( $display_value ) );

} else {
	echo $display_value;
}
