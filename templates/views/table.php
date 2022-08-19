<?php
/**
 * The table layout template
 *
 * @global \GV\Template_Context $gravityview
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

echo '<div id="' . esc_attr( $gravityview->view->get_anchor_id() ) . '">';

$gravityview->template->get_template_part( 'table/table', 'header' );
$gravityview->template->get_template_part( 'table/table', 'body' );
$gravityview->template->get_template_part( 'table/table', 'footer' );

echo '</div>';
