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

ob_start();

$gravityview->template->get_template_part( 'table/table', 'header' );
$gravityview->template->get_template_part( 'table/table', 'body' );
$gravityview->template->get_template_part( 'table/table', 'footer' );

$content = ob_get_clean();

/**
 * @filter `gravityview/view/wrapper_container` Modify the wrapper container.
 * @since  2.15
 *
 * @param string   $wrapper_container Wrapper container HTML markup
 * @param string   $anchor_id         (optional) Unique anchor ID to identify the view.
 * @param \GV\View $view              The View.
 */
$wrapper_container = apply_filters(
	'gravityview/view/wrapper_container',
	'<div id="' . esc_attr( $gravityview->view->get_anchor_id() ) . '">{content}</div>',
	$gravityview->view->get_anchor_id(),
	$gravityview->view
);

echo $wrapper_container ? str_replace( '{content}', $content, $wrapper_container ) : $content;
