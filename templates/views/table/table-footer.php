<?php
/**
 * The footer for the output table.
 *
 * @global \GV\Template_Context $gravityview
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

?>
	<tfoot>
		<tr>
			<?php $gravityview->template->the_columns(); ?>
		</tr>
		<?php gravityview_footer( $gravityview ); ?>
	</tfoot>
</table>
</div><!-- end .gv-table-container -->
<?php gravityview_after( $gravityview ); ?>
