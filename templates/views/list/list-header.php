<?php
/**
 * The header for the output list.
 *
 * @global \GV\Template_Context $gravityview
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

?>
<?php gravityview_before( $gravityview ); ?>
<div class="<?php gv_container_class( 'gv-list-container gv-list-multiple-container', true, $gravityview ); ?>">
	<?php gravityview_header( $gravityview ); ?>
