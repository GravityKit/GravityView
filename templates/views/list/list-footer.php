<?php
/**
 * The footer for the output list.
 *
 * @global \GV\Template_Context $gravityview
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

?>
	<?php gravityview_footer( $gravityview ); ?>
</div>
<?php gravityview_after( $gravityview ); ?>
